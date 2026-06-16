<?php
declare(strict_types=1);

// Prevent output buffering issues
while (ob_get_level()) {
    ob_end_clean();
}

// Configuration
require_once __DIR__ . '/includes/helpers.php';
$newsDataFile = __DIR__ . '/data/news.json';

// Helper function
function img_base_origin(): string {
    return base_origin();
}

// Load news data
$baseUrl = img_base_origin();
$imageItems = [];

if (file_exists($newsDataFile)) {
    $newsData = json_decode(file_get_contents($newsDataFile), true);
    
    if (!empty($newsData['items'])) {
        foreach ($newsData['items'] as $item) {
            $key = $item['key'] ?? '';
            if (empty($key)) continue;
            
            // Extract and resolve image URL (Force absolute for sitemaps)
            $imageUrl = resolve_image_url($item['hero_image'] ?? $item['thumbnail'] ?? $item['background_image'] ?? '', true);
            
            // Page URL where image appears
            $title = $item['headline'] ?? $item['title'] ?? 'Sports News Image';
            $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
            $slug = trim($slug, '-');
            $pageUrl = base_origin() . SITE_PATH . 'news/' . $slug . '-' . $key;
            
            // Image title and caption
            $title = $item['headline'] ?? $item['title'] ?? 'Sports News Image';
            $caption = $item['description'] ?? $item['summary'] ?? '';
            
            // Add to sitemap
            $imageItems[] = [
                'page_url' => $pageUrl,
                'image_url' => $imageUrl,
                'title' => htmlspecialchars($title, ENT_XML1, 'UTF-8'),
                'caption' => htmlspecialchars($caption, ENT_XML1, 'UTF-8')
            ];
        }
    }
}

// If no images, add a default entry
if (empty($imageItems)) {
    $imageItems[] = [
        'page_url' => base_origin() . SITE_PATH,
        'image_url' => resolve_image_url('favicon.ico', true),
        'title' => 'MaxPreps News',
        'caption' => 'High School Sports News and Highlights'
    ];
}

// Generate XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

foreach ($imageItems as $item) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>{$item['page_url']}</loc>\n";
    $xml .= "    <image:image>\n";
    $xml .= "      <image:loc>{$item['image_url']}</image:loc>\n";
    $xml .= "      <image:title>{$item['title']}</image:title>\n";
    if (!empty($item['caption'])) {
        $xml .= "      <image:caption>{$item['caption']}</image:caption>\n";
    }
    $xml .= "    </image:image>\n";
    $xml .= "  </url>\n";
}

$xml .= '</urlset>';

// Output
header('Content-Type: application/xml; charset=utf-8');
echo $xml;
flush();
?>
