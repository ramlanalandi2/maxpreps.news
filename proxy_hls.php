<?php
/**
 * HLS Proxy for MaxPreps Videos
 * Improved 2.0: Streaming support & Headers
 */

declare(strict_types=1);

// Prevent caching
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header('Access-Control-Allow-Origin: *'); // Allow CORS

require_once __DIR__ . '/includes/helpers.php';
$config = require __DIR__ . '/config.php';
$targetUrl = $_GET['url'] ?? '';

if (empty($targetUrl) || (strpos($targetUrl, 'http') !== 0)) {
    http_response_code(400);
    exit("Invalid URL");
}

// Security: Allow domain check
$allowedDomains = $config['allowed_hls_domains'] ?? ['maxpreps.io', 'maxpreps.com', 'nfhsnetwork.com'];
$host = parse_url($targetUrl, PHP_URL_HOST);
$allowed = false;
foreach ($allowedDomains as $domain) {
    if ($host && (substr($host, -strlen($domain)) === $domain)) {
        $allowed = true;
        break;
    }
}

// Also allow if it looks like a segment we just rewrote (could be different CDN)
// But strictly, we should stick to the allowlist.
if (!$allowed) {
    http_response_code(403);
    exit("Domain not allowed: $host");
}

$isPlaylist = (strpos($targetUrl, '.m3u8') !== false);
$isSegment = (strpos($targetUrl, '.ts') !== false);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $targetUrl,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 60, // Longer timeout for video
    // Imitate a real browser requesting from MaxPreps
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    CURLOPT_REFERER => $config['proxy_referer'] ?? 'https://www.maxpreps.com/',
    CURLOPT_SSL_VERIFYPEER => false, // Fix for some server configs
]);

if ($isPlaylist) {
    // --- PLAYLIST MODE (Buffer & Rewrite) ---
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    
    if ($httpCode !== 200) {
        http_response_code($httpCode);
        exit("Proxy Error: " . $httpCode);
    }

    header('Content-Type: application/vnd.apple.mpegurl');
    
    // Rewriting Logic
    $baseUrl = dirname($targetUrl) . '/';
    $lines = explode("\n", $response);
    $rewritten = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        if ($line[0] !== '#' && !filter_var($line, FILTER_VALIDATE_URL)) {
            // Relative URL -> Absolute -> Proxy
            $absoluteUrl = $baseUrl . $line;
            $line = "proxy_hls.php?url=" . urlencode($absoluteUrl);
        } elseif (filter_var($line, FILTER_VALIDATE_URL)) {
            // Absolute URL -> Proxy
            $line = "proxy_hls.php?url=" . urlencode($line);
        }
        $rewritten[] = $line;
    }
    echo implode("\n", $rewritten);

} else {
    // --- SEGMENT MODE (Direct Stream) ---
    // Pass essential headers from upstream? No, easier to just set type binary.
    // Ideally we assume standard TS type for segments.
    // But better to capture header from curl... hard with WRITEFUNCTION callback for headers.
    
    // We'll trust it's a video segment or key
    if ($isSegment) {
        header('Content-Type: video/mp2t');
    } else {
        header('Content-Type: application/octet-stream');
    }
    
    // Streaming callback
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
        echo $data;
        return strlen($data);
    });
    
    $success = curl_exec($ch);
    if (!$success) {
        error_log("Proxy Stream Error: " . curl_error($ch));
    }
}

curl_close($ch);
