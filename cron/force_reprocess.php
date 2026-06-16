<?php
// Force reprocess all existing news articles using the new aggressive rewriting logic
// This script loads data/news.json, passes items through NewsContentManager::rewriteContent,
// and saves the result back.

require_once __DIR__ . '/fetch_news.php';

echo "🚀 Starting Force Reprocess...\n";

$jsonPath = __DIR__ . '/../data/news.json';

if (!file_exists($jsonPath)) {
    die("❌ Error: data/news.json not found.\n");
}

// 1. Load existing data
$rawData = file_get_contents($jsonPath);
$data = json_decode($rawData, true);

if (!$data || !isset($data['items'])) {
    die("❌ Error: Invalid JSON data.\n");
}

$items = $data['items'];
$count = count($items);
echo "📅 Found $count articles to reprocess.\n";

// 2. Instantiate Manager
$manager = new NewsContentManager();

// 3. Rewrite Content
// rewriteContent expects an array of articles.
// It uses 'raw_content' from each article to generate new 'content' and 'content_text'.
$rewrittenItems = $manager->rewriteContent($items);

// 4. Update Data Structure
$data['items'] = $rewrittenItems;
$data['last_updated'] = gmdate('Y-m-d\TH:i:s+00:00');

// 5. Save back to disk
$newJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (file_put_contents($jsonPath, $newJson)) {
    echo "✅ Successfully reprocessed and saved $count articles.\n";
    echo "reprocess_success"; // Signal for checking output
} else {
    echo "❌ Error: Failed to write to news.json.\n";
}
