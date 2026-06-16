<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "--- Cloudflare Crawl API Verification ---\n";

$envFile = __DIR__ . '/.env';
$config = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $config[trim($name)] = trim($value);
    }
}

$cfAccountId = $config['CF_ACCOUNT_ID'] ?? null;
$cfApiToken = $config['CF_API_TOKEN'] ?? null;

if (!$cfAccountId || !$cfApiToken) {
    die("ERROR: Credentials not found in .env\n");
}

$testUrl = 'https://maxpreps.news/news/test-crawl-' . time();
echo "Testing crawl for: $testUrl\n";

$cfPayload = json_encode([
    'url' => $testUrl,
    'render' => true
]);

$cfCh = curl_init("https://api.cloudflare.com/client/v4/accounts/{$cfAccountId}/browser-rendering/crawl");
curl_setopt_array($cfCh, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $cfPayload,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$cfApiToken}",
        "Content-Type: application/json"
    ],
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2
]);

$cfResponse = curl_exec($cfCh);
$cfHttpCode = curl_getinfo($cfCh, CURLINFO_HTTP_CODE);
$cfError = curl_error($cfCh);
curl_close($cfCh);

echo "HTTP Code: $cfHttpCode\n";
echo "Error: $cfError\n";
echo "Response: $cfResponse\n";

if ($cfHttpCode >= 200 && $cfHttpCode < 300) {
    echo "\nSUCCESS: Cloudflare accepted the crawl job.\n";
} else {
    echo "\nFAILED: Check the response and error above.\n";
}
