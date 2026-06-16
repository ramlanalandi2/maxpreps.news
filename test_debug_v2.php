<?php
// test_debug_v2.php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>Environment Debug v2</h1>";

echo "<h2>Constants</h2>";
require_once __DIR__ . '/includes/helpers.php';
echo "BASE_DIR: " . (defined('BASE_DIR') ? BASE_DIR : "UNDEFINED") . "<br>";
echo "SITE_PATH: " . (defined('SITE_PATH') ? SITE_PATH : "UNDEFINED") . "<br>";
echo "__DIR__: " . __DIR__ . "<br>";

echo "<h2>Server Variables</h2>";
$vars = ['HTTP_HOST', 'SERVER_NAME', 'SCRIPT_NAME', 'SCRIPT_FILENAME', 'DOCUMENT_ROOT', 'REQUEST_URI'];
echo "<ul>";
foreach ($vars as $v) {
    echo "<li><strong>$v</strong>: " . ($_SERVER[$v] ?? 'N/A') . "</li>";
}
echo "</ul>";

echo "<h2>Asset Check</h2>";
$assets = [
    'assets/css/common.css',
    'assets/css/home.css',
    'template/images/nfhs-logo-outline.svg'
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Asset</th><th>Full Path</th><th>Exists?</th><th>Filemtime</th><th>get_asset_version</th></tr>";

foreach ($assets as $asset) {
    $fullPath = BASE_DIR . '/' . ltrim($asset, '/');
    $exists = file_exists($fullPath);
    $mtime = $exists ? filemtime($fullPath) : 'N/A';
    $version = function_exists('get_asset_version') ? get_asset_version($asset) : 'N/A';
    
    echo "<tr>";
    echo "<td>$asset</td>";
    echo "<td>$fullPath</td>";
    echo "<td>" . ($exists ? "✅ YES" : "❌ NO") . "</td>";
    echo "<td>$mtime</td>";
    echo "<td>$version</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>CURL SSL Test</h2>";
$testUrl = "https://www.maxpreps.news/api/event.php"; // Self-reference test
$ch = curl_init($testUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$res = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "CURL to $testUrl:<br>";
echo "HTTP Code: $code<br>";
if ($err) echo "Error: $err<br>";
echo "Response Length: " . strlen((string)$res) . "<br>";

if (function_exists('ob_get_level')) {
    echo "<h2>Output Buffering</h2>";
    echo "Level: " . ob_get_level() . "<br>";
}

echo "<h2>Web Asset Resolution Test</h2>";
$baseUrl = base_origin();
$cssUrl = $baseUrl . SITE_PATH . "assets/css/common.css";

echo "Fetching CSS via Web: $cssUrl <br>";
$ch = curl_init($cssUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HEADER => true // Get headers too
]);
$resp = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP Code: " . $info['http_code'] . "<br>";
echo "Content-Type: " . ($info['content_type'] ?? 'N/A') . "<br>";
if ($info['http_code'] >= 400) {
    echo "<p style='color:red'><strong>CRITICAL: Browser sees this as an error!</strong></p>";
}

echo "<h2>Index Page Link Test</h2>";
$indexUrl = $baseUrl . SITE_PATH;
echo "Fetching Index Page: $indexUrl <br>";
$ch = curl_init($indexUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$html = curl_exec($ch);
curl_close($ch);

if ($html) {
    if (preg_match_all('/<link[^>]+href=["\']([^"\']+)["\']/', (string)$html, $matches)) {
        echo "Found " . count($matches[1]) . " links:<br><ul>";
        foreach ($matches[1] as $link) {
            echo "<li>" . htmlspecialchars($link) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "No links found in HTML.<br>";
    }
} else {
    echo "Failed to fetch index HTML.<br>";
}
