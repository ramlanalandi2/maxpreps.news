<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
$config = require __DIR__ . '/config.php';

// Get search query
$query = trim($_GET['q'] ?? '');
$pageTitle = !empty($query) ? 'Search Results for "' . htmlspecialchars($query) . '"' : 'Search';
$siteName = $config['site_name'] ?? 'MaxPreps News';
$metaDescription = "Search $siteName for sports news, live games, and highlights.";

// Search results arrays
$newsResults = [];
$eventResults = [];
$totalResults = 0;

if (!empty($query)) {
    // Search in local news database
    $newsDataFile = __DIR__ . '/data/news.json';
    if (file_exists($newsDataFile)) {
        $newsData = json_decode(file_get_contents($newsDataFile), true);
        if (!empty($newsData['items'])) {
            foreach ($newsData['items'] as $item) {
                $headline = $item['headline'] ?? $item['title'] ?? '';
                $content = $item['content'] ?? '';
                $description = $item['description'] ?? '';
                
                // Simple search - check if query appears in headline, content, or description
                $searchText = strtolower($headline . ' ' . $content . ' ' . $description);
                $queryLower = strtolower($query);
                
                if (strpos($searchText, $queryLower) !== false) {
                    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $headline));
                    $slug = trim($slug, '-');
                    $nId = $item['key'] ?? ''; // Assuming 'key' is the ID for news items
                    $nUrl = SITE_PATH . "news/" . $slug . "-" . $nId;
                    $nThumb = resolve_image_url($item['hero_image'] ?? $item['thumbnail'] ?? '');
                    $newsResults[] = [
                        'title' => $headline,
                        'description' => $description,
                        'url' => $nUrl,
                        'image' => $nThumb,
                        'date' => $item['date'] ?? $item['published'] ?? date('Y-m-d')
                    ];
                }
            }
        }
    }
    
    // Search in NFHS events
    $searchUrl = 'https://search-api.nfhsnetwork.com/v3/search/events?q=' . urlencode($query) . '&card=true&size=20';
    $ch = curl_init($searchUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $eventsData = json_decode($response, true);
        if (!empty($eventsData['items'])) {
            foreach ($eventsData['items'] as $event) {
                $teams = extract_team_names($event);
                $title = $event['title'] ?? $event['name'] ?? '';
                
                $eventResults[] = [
                    'title' => $title,
                    'matchup' => ($teams['home'] && $teams['away']) ? $teams['home'] . ' vs ' . $teams['away'] : $title,
                    'url' => SITE_PATH . 'player.php?event=' . urlencode($event['key'] ?? ''),
                    'image' => $event['thumbnail'] ?? $event['background_image'] ?? '',
                    'date' => $event['eventDate'] ?? $event['date'] ?? '',
                    'status' => $event['status'] ?? 'upcoming'
                ];
            }
        }
    }
    
    $totalResults = count($newsResults) + count($eventResults);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | <?php echo htmlspecialchars($siteName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="robots" content="noindex, follow">
    <link rel="icon" href="<?php echo SITE_PATH; ?>favicon.ico" type="image/x-icon">
    <link rel="canonical" href="<?php echo base_origin() . SITE_PATH; ?>search.php<?php echo !empty($query) ? '?q=' . urlencode($query) : ''; ?>">
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/common.css">
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/home.css">
    <style>
        .search-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .search-header {
            margin-bottom: 30px;
        }
        .search-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .search-stats {
            color: #666;
            font-size: 0.95rem;
        }
        .search-form-large {
            max-width: 600px;
            margin: 30px auto;
        }
        .search-form-large form {
            display: flex;
            gap: 10px;
        }
        .search-form-large input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        .search-form-large button {
            padding: 15px 30px;
            background: #0066cc;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .search-form-large button:hover {
            background: #0052a3;
        }
        .results-section {
            margin-bottom: 50px;
        }
        .results-section h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #0066cc;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }
        .result-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: box-shadow 0.2s;
        }
        .result-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .result-item h3 {
            margin: 0 0 10px;
        }
        .result-item h3 a {
            color: #0066cc;
            text-decoration: none;
        }
        .result-item h3 a:hover {
            text-decoration: underline;
        }
        .result-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .result-description {
            color: #444;
            line-height: 1.6;
        }
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .no-results h2 {
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/templates/header.php'; ?>

<div class="search-container">
    <?php if (empty($query)): ?>
        <!-- Search landing page -->
        <div class="search-header">
            <h1>Search <?php echo htmlspecialchars($siteName); ?></h1>
            <p>Find sports news, live games, and highlights</p>
        </div>
        
        <div class="search-form-large">
            <form action="<?php echo SITE_PATH; ?>search.php" method="GET">
                <input type="search" name="q" placeholder="Search for teams, sports, news..." required autocomplete="off">
                <button type="submit">Search</button>
            </form>
        </div>
    <?php else: ?>
        <!-- Search results -->
        <div class="search-header">
            <h1>Search Results</h1>
            <p class="search-stats">
                <?php if ($totalResults > 0): ?>
                    Found <strong><?php echo $totalResults; ?></strong> result<?php echo $totalResults !== 1 ? 's' : ''; ?> for "<strong><?php echo htmlspecialchars($query); ?></strong>"
                <?php else: ?>
                    No results found for "<strong><?php echo htmlspecialchars($query); ?></strong>"
                <?php endif; ?>
            </p>
        </div>

        <?php if (!empty($newsResults)): ?>
            <div class="results-section">
                <h2>📰 News Articles (<?php echo count($newsResults); ?>)</h2>
                <?php foreach ($newsResults as $result): ?>
                    <div class="result-item">
                        <h3><a href="<?php echo htmlspecialchars($result['url']); ?>"><?php echo htmlspecialchars($result['title']); ?></a></h3>
                        <div class="result-meta">
                            📅 <?php echo date('F j, Y', strtotime($result['date'])); ?>
                        </div>
                        <?php if (!empty($result['description'])): ?>
                            <div class="result-description">
                                <?php echo htmlspecialchars(substr(strip_tags($result['description']), 0, 200)); ?>...
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($eventResults)): ?>
            <div class="results-section">
                <h2>🏀 Live Games & Events (<?php echo count($eventResults); ?>)</h2>
                <?php foreach ($eventResults as $result): ?>
                    <div class="result-item">
                        <h3><a href="<?php echo htmlspecialchars($result['url']); ?>"><?php echo htmlspecialchars($result['matchup']); ?></a></h3>
                        <div class="result-meta">
                            📅 <?php echo !empty($result['date']) ? date('F j, Y', strtotime($result['date'])) : 'Date TBA'; ?> • 
                            <?php echo ucfirst($result['status']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($totalResults === 0): ?>
            <div class="no-results">
                <h2>No results found</h2>
                <p>Try different keywords or browse our <a href="<?php echo SITE_PATH; ?>" style="color: #0066cc;">homepage</a> for latest content.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>
