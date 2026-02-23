<?php

namespace App\Domain\PostalCode\Contracts;

/**
 * Interface for PostCode database operations.
 *
 * Encapsulates all SQL operations needed for importing
 * postal codes from temporary tables and CSV files.
 */
interface ImportPostCodeRepositoryInterface
{
    /**
     * Creates a temporary table for CSV import.
     */
    public function createTempTable(): void;

    /**
     * Drops the temporary table if it exists.
     */
    public function dropTempTable(): void;

    /**
     * Loads CSV data into the temporary table.
     *
     * @param string $csvPath Path to the CSV file.
     */
    public function loadCsvIntoTemp(string $csvPath): void;

    /**
     * Inserts new postal codes or updates existing ones from the temporary table.
     */
    public function insertOrUpdateFromTemp(): void;

    /**
     * Deletes postal codes missing from the temporary table, except API-created ones.
     */
    public function deleteMissingFromTemp(): void;
}
