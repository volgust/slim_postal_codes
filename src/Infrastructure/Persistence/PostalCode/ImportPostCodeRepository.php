<?php

namespace App\Infrastructure\Persistence\PostalCode;

use PDO;
use App\Domain\PostalCode\Contracts\ImportPostCodeRepositoryInterface;

/**
 * MySQL implementation of PostCodeRepositoryInterface.
 *
 * Handles all database operations related to postal codes,
 * including temporary table creation, CSV import, insert/update, and cleanup.
 */
class ImportPostCodeRepository implements ImportPostCodeRepositoryInterface
{
    /**
     * @param PDO $pdo PDO connection for database operations.
     */
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Creates temporary table for CSV import.
     */
    public function createTempTable(): void
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
     * Drops the temporary table if it exists.
     */
    public function dropTempTable(): void
    {
        $this->pdo->exec("DROP TEMPORARY TABLE IF EXISTS tmp_post_codes");
    }

    /**
     * Loads CSV data into the temporary table using MySQL LOAD DATA LOCAL INFILE.
     *
     * @param string $csvPath Path to the CSV file.
     */
    public function loadCsvIntoTemp(string $csvPath): void
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
     * Inserts new records or updates existing postal codes from temporary table.
     */
    public function insertOrUpdateFromTemp(): void
    {
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
    }

    /**
     * Deletes postal codes missing from temporary table, except API-created ones.
     */
    public function deleteMissingFromTemp(): void
    {
        $this->pdo->exec("
            DELETE l FROM postal_codes l
            LEFT JOIN tmp_post_codes t
                ON l.post_code = t.post_code
            WHERE t.post_code IS NULL
              AND l.api_created = 0
        ");
    }
}
