<?php

declare(strict_types=1);

// Prevent output buffering issues
while (ob_get_level()) {
    ob_end_clean();
}

// Configuration
require_once __DIR__ . '/includes/helpers.php';
$newsDataFile = __DIR__ . '/data/news.json';

// Basic Helper Functions
function articles_base_origin(): string
{
    return base_origin();
}

// Load News from Local JSON
$baseUrl = articles_base_origin();
$newsItems = [];

// Priority configuration
$priority = '0.8';
$changefreq = 'daily';

if (file_exists($newsDataFile)) {
    $newsData = json_decode(file_get_contents($newsDataFile), true);

    if (!empty($newsData['items'])) {
        foreach ($newsData['items'] as $item) {
            $key = $item['key'] ?? '';
            if (empty($key)) continue;

            // Date extraction
            $dateStr = $item['date'] ?? $item['published'] ?? null;
            if (!$dateStr) {
                $dateStr = date('Y-m-d H:i:s');
            }

            try {
                $date = new DateTime($dateStr);
                $title = $item['headline'] ?? $item['title'] ?? 'News';
                $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
                $slug = trim($slug, '-');
                $loc = base_origin() . SITE_PATH . 'news/' . $slug . '-' . $key;

                // Add to list
                $newsItems[] = [
                    'loc' => $loc,
                    'lastmod' => $date->format('c'),
                    'priority' => $priority,
                    'changefreq' => $changefreq
                ];
            } catch (Exception $e) {
                continue;
            }
        }
    }
}

// Ensure at least one item
if (empty($newsItems)) {
    $newsItems[] = [
        'loc' => base_origin() . SITE_PATH,
        'lastmod' => (new DateTime())->format('c'),
        'priority' => '1.0',
        'changefreq' => 'hourly'
    ];
}

// Generate XML (Standard Sitemap)
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($newsItems as $item) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($item['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
    $xml .= "    <lastmod>{$item['lastmod']}</lastmod>\n";
    $xml .= "    <changefreq>{$item['changefreq']}</changefreq>\n";
    $xml .= "    <priority>{$item['priority']}</priority>\n";
    $xml .= "  </url>\n";
}

$xml .= '</urlset>';

// Output
header('Content-Type: application/xml; charset=utf-8');
echo trim($xml);
flush();
