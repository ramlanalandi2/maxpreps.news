<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$_GET['id'] = 'gam077cb690f7';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';

try {
    // Redefine send_json_response to catch the data
    if (!function_exists('send_json_response')) {
        function send_json_response(array $data, int $code = 200): void {
            echo "\n--- API RESPONSE ($code) ---\n";
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            echo "\n--- END API RESPONSE ---\n";
            exit;
        }
    }

    include 'c:/xampp/htdocs/maxpreps.news/api/event.php';
} catch (Throwable $e) {
    echo "\nFATAL ERROR/EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
