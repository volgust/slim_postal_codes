<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Application\PostalCode\Services\PostCodeImportService;

$pdo = require __DIR__ . '/../config/database.php';

$archivePath = $argv[1] ?? null;

if (!$archivePath) {
    echo "Usage: php bin/import_post_codes.php ../archive/postindex.zip\n";
    exit(1);
}

$service = new PostCodeImportService($pdo);
$service->import($archivePath);

echo "Import completed.\n";
