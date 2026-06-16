<?php
declare(strict_types=1);

while (ob_get_level()) {
    ob_end_clean();
}

// Configuration
require_once __DIR__ . '/includes/helpers.php';
$newsDataFile = __DIR__ . '/data/news.json';

// Independent Helpers
function vid_base_origin(): string {
    return base_origin();
}

function vid_http_get(string $url): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Googlebot-Video/1.0)',
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($httpCode === 200 && $response) ? json_decode($response, true) : null;
}

// Fetch Video Content (On Demand & Live)
$baseUrl = vid_base_origin();
$urls = [
    'ondemand' => 'https://search-api.nfhsnetwork.com/v3/search/events/ondemand?card=true&size=50',
    'live' => 'https://search-api.nfhsnetwork.com/v3/search/events/live?card=true&size=20'
];

$videoItems = [];
$seenKeys = [];

foreach ($urls as $status => $url) {
    $data = vid_http_get($url);
    if (empty($data['items'])) continue;

    foreach ($data['items'] as $item) {
        $key = $item['key'] ?? '';
        if (empty($key) || isset($seenKeys[$key])) continue;

        // Required Video Fields
        $thumbnail = $item['thumbnail'] ?? $item['background_image'] ?? 'https://social.nfhsnetwork.com/default_share.png';
        $title = htmlspecialchars($item['headline'] ?? $item['title'] ?? 'High School Sports Video');
        $desc = htmlspecialchars($item['description'] ?? "Watch {$title} on " . ($config['site_title'] ?? 'SportsMediaTV') . ".");
        $dateStr = $item['date'] ?? $item['startTime'] ?? null;
        
        if (!$dateStr) continue;

        try {
            $date = new DateTime($dateStr);
            // Inside sitemap-video.php foreach loop
            $hlsStream = $item['hls_url'] ?? $item['stream_url'] ?? '';

            // If the API provides a direct stream, use it. Otherwise, construct an embed URL format.
            $playerLoc = !empty($hlsStream) ? $hlsStream : base_origin() . SITE_PATH . 'embed.php?event=' . urlencode($key);

            $videoItems[] = [
            'loc' => base_origin() . SITE_PATH . 'player.php?event=' . urlencode($key),
            'thumbnail_loc' => $thumbnail,
            'title' => $title,
            'description' => $desc,
            'publication_date' => $date->format('c'),
    
            // CRITICAL FIX: Must point to a stream or isolated iframe embed, NOT the HTML page
            'player_loc' => $playerLoc, 
            'duration' => '6000' 
        ];
            $seenKeys[$key] = true;
        } catch (Exception $e) { continue; }
    }
}

// Generate XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";

foreach ($videoItems as $item) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>{$item['loc']}</loc>\n";
    $xml .= "    <video:video>\n";
    $xml .= "      <video:thumbnail_loc>{$item['thumbnail_loc']}</video:thumbnail_loc>\n";
    $xml .= "      <video:title>{$item['title']}</video:title>\n";
    $xml .= "      <video:description>{$item['description']}</video:description>\n";
    $xml .= "      <video:player_loc allow_embed=\"yes\" autoplay=\"ap=1\">{$item['player_loc']}</video:player_loc>\n";
    $xml .= "      <video:publication_date>{$item['publication_date']}</video:publication_date>\n";
    $xml .= "      <video:family_friendly>yes</video:family_friendly>\n";
    $xml .= "      <video:live>no</video:live>\n"; // Default to no for safety
    $xml .= "    </video:video>\n";
    $xml .= "  </url>\n";
}

$xml .= '</urlset>';

header('Content-Type: application/xml; charset=utf-8');
echo $xml;
flush();
?>
