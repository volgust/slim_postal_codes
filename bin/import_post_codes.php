<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\PostalCode\ImportPostCodeRepository;
use App\Application\PostalCode\Services\ImportPostCodeService;
use App\Domain\PostalCode\Contracts\ImportPostCodeRepositoryInterface;

/**
 * CLI script to import postal codes from a ZIP archive.
 *
 * Usage:
 *   php bin/import_post_codes.php /path/to/postindex.zip
 *
 * This script:
 *  - Loads the PDO connection from configuration
 *  - Wraps it in an ImportPostCodeRepository
 *  - Uses ImportPostCodeService to perform the import
 *  - Outputs success or error messages to the console
 *
 * @param array $argv CLI arguments
 *   - $argv[0]: script name
 *   - $argv[1]: path to ZIP archive
 *
 * @return void
 */
function main(array $argv): void
{
    /** @var string|null $archivePath Path to the ZIP archive from CLI arguments */
    $archivePath = $argv[1] ?? null;

    if (!$archivePath) {
        echo "Usage: php bin/import_post_codes.php /path/to/postindex.zip\n";
        exit(1);
    }

    /** @var \PDO $pdo PDO database connection loaded from configuration */
    $pdo = require __DIR__ . '/../config/database.php';

    /** @var ImportPostCodeRepositoryInterface $repository Repository for postal code operations */
    $repository = new ImportPostCodeRepository($pdo);

    /** @var ImportPostCodeService $service Service responsible for importing postal codes */
    $service = new ImportPostCodeService($repository);

    try {
        $service->import($archivePath);
        echo "Import completed successfully.\n";
    } catch (\Throwable $e) {
        // Handle errors gracefully in CLI
        fwrite(STDERR, "Import failed: " . $e->getMessage() . "\n");
        exit(1);
    }
}

// Execute the CLI script
main($argv);
