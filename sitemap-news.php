<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| HARD SAFETY: Prevent ANY output before XML
|--------------------------------------------------------------------------
*/
error_reporting(0);
ini_set('display_errors', '0');

while (ob_get_level()) {
    ob_end_clean();
}

/*
|--------------------------------------------------------------------------
| CONFIGURATION
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/includes/helpers.php';
$config = require __DIR__ . '/config.php';
$newsDataFile = __DIR__ . '/data/news.json';

/*
|--------------------------------------------------------------------------
| Helper: Detect Base URL
|--------------------------------------------------------------------------
*/
function news_base_origin(): string
{
    return base_origin();
}

/*
|--------------------------------------------------------------------------
| Site Name
|--------------------------------------------------------------------------
*/
$siteName = $config['site_name'] ?? 'MaxPreps News';

/*
|--------------------------------------------------------------------------
| Load News
|--------------------------------------------------------------------------
*/
$baseUrl = news_base_origin();
$newsItems = [];

if (file_exists($newsDataFile)) {

    $newsData = json_decode(file_get_contents($newsDataFile), true);

    if (!empty($newsData['items']) && is_array($newsData['items'])) {

        $twoDaysAgo = (new DateTime())->modify('-2 days');

        foreach ($newsData['items'] as $item) {

            $key = $item['key'] ?? '';
            if (empty($key)) {
                continue;
            }

            // FIX: Define title properly
            $title = $item['title'] ?? $item['headline'] ?? 'High School Sports News';

            // Date extraction
            $dateStr = $item['date'] ?? $item['published'] ?? date('Y-m-d H:i:s');

            try {
                $date = new DateTime($dateStr);

                // Google News: Only last 2 days
                if ($date < $twoDaysAgo) {
                    continue;
                }

                // Slug
                $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
                $slug = trim($slug, '-');

                $seoUrl = base_origin() . SITE_PATH . 'news/' . $slug . '-' . $key;

                $newsItems[] = [
                    'loc'      => htmlspecialchars($seoUrl, ENT_XML1, 'UTF-8'),
                    'name'     => htmlspecialchars($siteName, ENT_XML1, 'UTF-8'),
                    'language' => 'en',
                    'date'     => $date->format('c'),
                    'lastmod'  => $date->format('c'),
                    'title'    => htmlspecialchars($title, ENT_XML1, 'UTF-8'),
                ];
            } catch (Exception $e) {
                continue;
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Fallback (Avoid Empty Sitemap)
|--------------------------------------------------------------------------
*/
if (empty($newsItems)) {

    $now = new DateTime();

    $newsItems[] = [
        'loc'      => htmlspecialchars(base_origin() . SITE_PATH, ENT_XML1, 'UTF-8'),
        'name'     => htmlspecialchars($siteName, ENT_XML1, 'UTF-8'),
        'language' => 'en',
        'date'     => $now->format('c'),
        'lastmod'  => $now->format('c'),
        'title'    => htmlspecialchars('High School Sports News', ENT_XML1, 'UTF-8'),
    ];
}

/*
|--------------------------------------------------------------------------
| OUTPUT XML
|--------------------------------------------------------------------------
*/
header('Content-Type: application/xml; charset=utf-8');

// Generate XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

foreach ($newsItems as $item) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>{$item['loc']}</loc>\n";
    $xml .= "    <lastmod>{$item['lastmod']}</lastmod>\n";
    $xml .= "    <news:news>\n";
    $xml .= "      <news:publication>\n";
    $xml .= "        <news:name>{$item['name']}</news:name>\n";
    $xml .= "        <news:language>{$item['language']}</news:language>\n";
    $xml .= "      </news:publication>\n";
    $xml .= "      <news:publication_date>{$item['date']}</news:publication_date>\n";
    $xml .= "      <news:title>{$item['title']}</news:title>\n";
    $xml .= "    </news:news>\n";
    $xml .= "  </url>\n";
}

$xml .= '</urlset>';

echo trim($xml);
flush();
exit;
