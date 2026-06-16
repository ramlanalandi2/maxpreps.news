<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "Testing auto_indexing.php inclusion...\n";

$filePath = __DIR__ . '/auto_indexing.php';
if (!file_exists($filePath)) {
    die("Error: auto_indexing.php not found at $filePath\n");
}

echo "File exists. Requiring file...\n";
require_once $filePath;

if (function_exists('auto_index_if_needed')) {
    echo "SUCCESS: Function auto_index_if_needed() is defined.\n";
} else {
    echo "ERROR: Function auto_index_if_needed() is NOT defined.\n";
}
