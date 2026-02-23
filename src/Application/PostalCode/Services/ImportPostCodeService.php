<?php

namespace App\Application\PostalCode\Services;

use App\Domain\PostalCode\Contracts\ImportPostCodeRepositoryInterface;
use ZipArchive;
use XMLReader;

/**
 * Service responsible for importing postal codes from XLSX archives.
 *
 * Handles:
 * - Extracting XLSX files from ZIP archives
 * - Parsing XLSX into CSV (streaming, low memory)
 * - Loading CSV data into a temporary table via repository
 * - Synchronizing with the main postal_codes table (insert, update, delete)
 */
class ImportPostCodeService
{
    /**
     * @param PostCodeRepositoryInterface $repository Repository for database operations.
     */
    public function __construct(private ImportPostCodeRepositoryInterface $repository)
    {
    }

    /**
     * Executes the full import process from a ZIP archive.
     *
     * @param string $archivePath Path to the ZIP archive containing the XLSX file.
     *
     * @throws \RuntimeException If files are missing or invalid.
     * @throws \Throwable If any repository operation fails.
     */
    public function import(string $archivePath): void
    {
        $this->repository->createTempTable();

        try {
            $xlsxPath = $this->unzip($archivePath);
            $csvPath  = $this->convertXlsxToCsvStreaming($xlsxPath);

            $this->repository->loadCsvIntoTemp($csvPath);
            $this->repository->insertOrUpdateFromTemp();
            $this->repository->deleteMissingFromTemp();
        } finally {
            $this->repository->dropTempTable();
        }
    }

    /**
     * Extracts the first XLSX file from a ZIP archive.
     *
     * @param string $archivePath Path to ZIP archive.
     * @return string Path to extracted XLSX file.
     * @throws \RuntimeException If archive cannot be opened or XLSX missing.
     */
    private function unzip(string $archivePath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException('Cannot open archive');
        }

        $extractPath = sys_get_temp_dir() . '/postcodes_' . uniqid('', true);
        if (!mkdir($extractPath, 0777, true) && !is_dir($extractPath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $extractPath));
        }
        $zip->extractTo($extractPath);
        $zip->close();

        $files = glob($extractPath . '/*.xlsx');
        if (!$files) {
            throw new \RuntimeException('XLSX file not found in archive');
        }

        return $files[0];
    }

    /**
     * Converts XLSX worksheet to CSV using streaming (low memory).
     *
     * @param string $xlsxPath Path to XLSX file.
     * @return string Path to generated CSV.
     *
     * @throws \RuntimeException If required columns are missing.
     */
    private function convertXlsxToCsvStreaming(string $xlsxPath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($xlsxPath) !== true) {
            throw new \RuntimeException('Cannot open XLSX file');
        }

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
                $cellRef = $xml->getAttribute('r');
                preg_match('/[A-Z]+/', $cellRef, $matches);
                $colIndex = isset($matches[0]) ? $this->columnLetterToIndex($matches[0]) : count($row);
                $type = $xml->getAttribute('t');
                $xml->read();

                if ($xml->nodeType === XMLReader::ELEMENT && $xml->name === 'v') {
                    $xml->read();
                    $value = (string) $xml->value;
                    if ($type === 's') {
                        $value = $sharedStrings[(int) $value] ?? '';
                    }
                    $row[$colIndex] = trim($value);
                }
            }

            if ($xml->nodeType === XMLReader::END_ELEMENT && $xml->name === 'row') {
                $rowIndex++;
                if ($rowIndex === 1) {
                    // HEADER MAPPING
                    foreach ($row as $i => $col) {
                        $colLower = mb_strtolower(trim((string)$col));
                        $colLower = preg_replace('/\s+/', ' ', $colLower);
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
                        continue;
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
     * Converts Excel column letters to zero-based index.
     *
     * @param string $letter Column letter.
     * @return int Zero-based index.
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

        return $index - 1;
    }
}
