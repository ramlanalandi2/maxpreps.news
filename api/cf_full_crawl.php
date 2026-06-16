<?php
declare(strict_types=1);

/**
 * AJAX API: Cloudflare Full Website Crawl Trigger
 * Cooldown: 24 hours
 */

header('Content-Type: application/json');

$envFile = dirname(__DIR__) . '/.env';
$lockFile = dirname(__DIR__) . '/cf_full_crawl.lock';
$cooldown = 86400; // 24 hours in seconds

// 1. Check cooldown
if (file_exists($lockFile)) {
    $lastRun = (int)file_get_contents($lockFile);
    if ((time() - $lastRun) < $cooldown) {
        echo json_encode(['success' => false, 'message' => 'Full crawl is on cooldown.']);
        exit;
    }
}

// 2. Load Config
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
$baseUrl = 'https://maxpreps.news';

if (!$cfAccountId || !$cfApiToken) {
    echo json_encode(['success' => false, 'message' => 'Cloudflare credentials missing.']);
    exit;
}

// 3. Trigger Crawl
$cfPayload = json_encode([
    'url' => $baseUrl,
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
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2
]);

$cfResponse = curl_exec($cfCh);
$cfHttpCode = curl_getinfo($cfCh, CURLINFO_HTTP_CODE);
curl_close($cfCh);

if ($cfHttpCode === 200) {
    // Update lock file on success
    file_put_contents($lockFile, (string)time());
    $logMsg = "[" . date('Y-m-d H:i:s') . "] AJAX Full Crawl Success: $cfResponse\n";
    file_put_contents(dirname(__DIR__) . '/cloudflare_crawl.log', $logMsg, FILE_APPEND);
    echo json_encode(['success' => true, 'message' => 'Full crawl triggered successfully.']);
} else {
    $logMsg = "[" . date('Y-m-d H:i:s') . "] AJAX Full Crawl Failed ($cfHttpCode): $cfResponse\n";
    file_put_contents(dirname(__DIR__) . '/cloudflare_crawl.log', $logMsg, FILE_APPEND);
    echo json_encode(['success' => false, 'message' => "Cloudflare API error ($cfHttpCode).", 'response' => json_decode($cfResponse)]);
}
