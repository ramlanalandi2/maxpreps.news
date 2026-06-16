<?php
require_once 'c:/xampp/htdocs/maxpreps.news/includes/helpers.php';
require_once 'c:/xampp/htdocs/maxpreps.news/includes/cache.php';

// Simulate the call that player.php/index.php makes
function http_get_json_minimal(string $url): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: NFHS-Test/1.0',
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "URL: $url\n";
    echo "HTTP CODE: $httpCode\n";
    if ($curlError) echo "CURL ERROR: $curlError\n";
    
    return json_decode((string)$response, true);
}

$origin = base_origin(); // This might be http://localhost on CLI
$eventId = 'gam077cb690f7';
$apiUrl = rtrim($origin, '/') . '/api/event.php?id=' . urlencode($eventId);

echo "Testing Local API Connection via CURL...\n";
$data = http_get_json_minimal($apiUrl);

if ($data) {
    echo "SUCCESS: API responded with JSON.\n";
    // echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "FAILURE: Could not fetch local API.\n";
    
    // Test base origin components
    echo "\nDEBUG INFO:\n";
    echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
    echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "\n";
}
