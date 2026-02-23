<?php

namespace App\Application\PostalCode\Services;

use PDO;
use ZipArchive;
use XMLReader;

/**
 * Service responsible for importing postal codes from XLSX archives.
 *
 * Handles:
 * - Extracting XLSX files from ZIP archives
 * - Parsing XLSX into CSV (streaming, low memory)
 * - Loading CSV data into a temporary table
 * - Synchronizing with the main postal_codes table (insert, update, delete)
 */
class PostCodeImportService
{
    /**
     * PDO connection for database operations.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Constructor.
     *
     * @param PDO $pdo PDO connection for database operations.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Main import method.
     *
     * Performs the full import process:
     * - Extracts XLSX from archive
     * - Converts XLSX to CSV
     * - Loads CSV into temporary table
     * - Syncs data into main table
     * - Cleans up temporary table
     *
     * @param string $archivePath Path to the ZIP archive containing the XLSX file.
     *
     * @throws \RuntimeException on file errors or missing columns.
     * @throws \Throwable on any database or processing error (rolls back transaction).
     */
    public function import(string $archivePath): void
    {
        $this->pdo->beginTransaction();

        try {
            $xlsxPath = $this->unzip($archivePath);
            $csvPath  = $this->convertXlsxToCsvStreaming($xlsxPath);

            $this->createTempTable();
            $this->loadCsvIntoTemp($csvPath);
            $this->syncData();
            $this->dropTempTable();

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Extracts the first XLSX file from a ZIP archive.
     *
     * @param string $archivePath Path to the ZIP archive.
     * @return string Path to the extracted XLSX file.
     *
     * @throws \RuntimeException If the archive cannot be opened or no XLSX file is found.
     */
    private function unzip(string $archivePath): string
    {
        $zip = new ZipArchive();

        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException('Cannot open archive');
        }

        $extractPath = sys_get_temp_dir() . '/postcodes_' . uniqid('', true);
        mkdir($extractPath, 0777, true);

        $zip->extractTo($extractPath);
        $zip->close();

        $files = glob($extractPath . '/*.xlsx');

        if (!$files) {
            throw new \RuntimeException('XLSX file not found in archive');
        }

        return $files[0];
    }

    /**
     * Converts XLSX worksheet to CSV using streaming (constant memory).
     *
     * Skips rows with empty post_code.
     *
     * @param string $xlsxPath Path to the XLSX file.
     * @return string Path to the generated CSV file.
     *
     * @throws \RuntimeException If required columns are missing.
     */
    private function convertXlsxToCsvStreaming(string $xlsxPath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($xlsxPath) !== true) {
            throw new \RuntimeException('Cannot open XLSX file');
        }

        // Load shared strings
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $sharedStrings = [];
        if ($sharedStringsXml) {
            $reader = new XMLReader();
            $reader->XML($sharedStringsXml);
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 't') {
                    $reader->read();
                    $sharedStrings[] = (string) $reader->value;
                }
            }
            $reader->close();
        }

        // Load worksheet
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            throw new \RuntimeException('Cannot read worksheet');
        }

        $tempCsv = tempnam(sys_get_temp_dir(), 'postcodes_') . '.csv';
        $handle  = fopen($tempCsv, 'w');

        $xml = new XMLReader();
        $xml->XML($sheetXml);

        $row = [];
        $rowIndex = 0;
        $headerMap = [];

        while ($xml->read()) {
            if ($xml->nodeType === XMLReader::ELEMENT && $xml->name === 'row') {
                $row = [];
            }

            if ($xml->nodeType === XMLReader::ELEMENT && $xml->name === 'c') {
                $cellRef = $xml->getAttribute('r'); // e.g., "A1", "B1"
                preg_match('/[A-Z]+/', $cellRef, $matches);
                $colIndex = isset($matches[0]) ? $this->columnLetterToIndex($matches[0]) : count($row);
                $type = $xml->getAttribute('t'); // type attribute
                $xml->read(); // move to <v>

                if ($xml->nodeType === XMLReader::ELEMENT && $xml->name === 'v') {
                    $xml->read();
                    $value = (string) $xml->value;
                    if ($type === 's') {
                        $value = $sharedStrings[(int) $value] ?? '';
                    }
                    $row[$colIndex] = trim($value); // place in the correct column
                }
            }

            if ($xml->nodeType === XMLReader::END_ELEMENT && $xml->name === 'row') {
                $rowIndex++;

                if ($rowIndex === 1) {
                    // HEADER MAPPING
                    foreach ($row as $i => $col) {
                        $colLower = mb_strtolower(trim((string)$col));
                        $colLower = preg_replace('/\s+/', ' ', $colLower); // collapse spaces

                        if (str_contains($colLower, 'region')) {
                            $headerMap['region'] = $i;
                        } elseif (str_contains($colLower, 'district')) {
                            $headerMap['district'] = $i;
                        } elseif (str_contains($colLower, 'settlement')) {
                            $headerMap['settlement'] = $i;
                        } elseif (str_contains($colLower, 'post office') && !str_contains($colLower, 'code')) {
                            $headerMap['post_office'] = $i;
                        } elseif (str_contains($colLower, 'postal code')) {
                            $headerMap['post_code'] = $i;
                        }
                    }

                    // verify all required columns
                    $required = ['region','district','settlement','post_office','post_code'];
                    foreach ($required as $key) {
                        if (!isset($headerMap[$key])) {
                            throw new \RuntimeException("Missing required column: $key");
                        }
                    }

                    continue;
                }

                if (!empty($row)) {
                    $mapped = [
                        $row[$headerMap['region']] ?? '',
                        $row[$headerMap['district']] ?? '',
                        $row[$headerMap['settlement']] ?? '',
                        $row[$headerMap['post_office']] ?? '',
                        $row[$headerMap['post_code']] ?? '',
                    ];

                    if (trim($mapped[4]) === '') {
                        continue; // skip this row
                    }

                    fputcsv($handle, $mapped);
                }
            }
        }

        fclose($handle);
        $zip->close();

        return $tempCsv;
    }

    /**
     * Converts Excel column letters (A, B, ..., AA, etc.) to 0-based index.
     *
     * @param string $letter Column letter.
     * @return int Column index (0-based).
     */
    private function columnLetterToIndex(string $letter): int
    {
        $letter = strtoupper($letter);
        $length = strlen($letter);
        $index = 0;

        for ($i = 0; $i < $length; $i++) {
            $index *= 26;
            $index += ord($letter[$i]) - ord('A') + 1;
        }

        return $index - 1; // 0-based index
    }

    /**
     * Creates temporary table for CSV import.
     */
    private function createTempTable(): void
    {
        $this->pdo->exec("
            CREATE TEMPORARY TABLE tmp_post_codes (
                region VARCHAR(191),
                district VARCHAR(191),
                settlement VARCHAR(191),
                post_office VARCHAR(191),
                post_code CHAR(5) PRIMARY KEY
            ) ENGINE=InnoDB
        ");
    }

    /**
     * Loads CSV data into temporary table using MySQL LOAD DATA LOCAL INFILE.
     *
     * @param string $csvPath Path to CSV file.
     */
    private function loadCsvIntoTemp(string $csvPath): void
    {
        $stmt = $this->pdo->prepare("
            LOAD DATA LOCAL INFILE :file
            INTO TABLE tmp_post_codes
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\n'
            (region, district, settlement, post_office, post_code)
        ");

        $stmt->execute(['file' => $csvPath]);
    }

    /**
     * Synchronizes data from temporary table into main postal_codes table.
     *
     * Inserts new records, updates changed records, and deletes missing ones
     * (except those created via API).
     */
    private function syncData(): void
    {
        // INSERT new + UPDATE changed
        $this->pdo->exec("
            INSERT INTO postal_codes
                (region, district, settlement, post_office, post_code, api_created)
            SELECT
                t.region,
                t.district,
                t.settlement,
                t.post_office,
                t.post_code,
                0
            FROM tmp_post_codes t
            ON DUPLICATE KEY UPDATE
                region = VALUES(region),
                district = VALUES(district),
                settlement = VALUES(settlement),
                post_office = VALUES(post_office)
        ");

        // DELETE missing (except API created)
        $this->pdo->exec("
            DELETE l FROM postal_codes l
            LEFT JOIN tmp_post_codes t
                ON l.post_code = t.post_code
            WHERE t.post_code IS NULL
              AND l.api_created = 0
        ");
    }

    /**
     * Drops the temporary table.
     */
    private function dropTempTable(): void
    {
        $this->pdo->exec("DROP TEMPORARY TABLE IF EXISTS tmp_post_codes");
    }
}
