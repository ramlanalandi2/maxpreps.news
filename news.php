<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/trending.php';


// === SPORT DETECTOR ===
function detect_sport_from_title(string $title): ?string {
    $sports = ['football', 'basketball', 'baseball', 'soccer', 'volleyball', 'softball', 'wrestling'];

    $titleLower = strtolower($title);

    foreach ($sports as $sport) {
        if (strpos($titleLower, $sport) !== false) {
            return $sport;
        }
    }

    return null;
}

// === TREND MATCHER ===
function match_trend_with_sport(array $trends, string $sport): ?string {
    $sport = strtolower($sport);

    foreach ($trends as $trend) {
        $trendLower = strtolower($trend);

        if (strpos($trendLower, $sport) !== false) {
            return $trend;
        }

        if ($sport === 'basketball' && strpos($trendLower, 'nba') !== false) {
            return $trend;
        }

        if ($sport === 'football' && strpos($trendLower, 'nfl') !== false) {
            return $trend;
        }

        if ($sport === 'baseball' && strpos($trendLower, 'mlb') !== false) {
            return $trend;
        }
    }

    return null;
}
// Load caching system if available
if (file_exists(__DIR__ . '/includes/cache.php')) {
    require_once __DIR__ . '/includes/cache.php';
}
require_once __DIR__ . '/includes/helpers.php';
$config = require __DIR__ . '/config.php';

// (base_origin is now provided by helpers.php)


// ✅ NEWS CONTROLLER
$newsId = $_GET['id'] ?? $_GET['news'] ?? '';
$newsArticle = null;

if (!empty($newsId)) {
    $newsDataFile = __DIR__ . '/data/news.json';
    if (file_exists($newsDataFile)) {
        $allNews = json_decode(file_get_contents($newsDataFile), true);
        $items = $allNews['items'] ?? [];
        foreach ($items as $item) {
            if (isset($item['key']) && $item['key'] === $newsId) {
                $newsArticle = $item;
                break;
            }
        }
    }
}

if (!$newsArticle) {
    header("Location: " . SITE_PATH);
    exit;
}

// Extract Data
$h1Title = $newsArticle['headline'] ?? $newsArticle['title'] ?? 'News Update';
$heroImage = resolve_image_url($newsArticle['hero_image'] ?? $newsArticle['background_image'] ?? $newsArticle['thumbnail'] ?? '', true);
$summary = $newsArticle['description'] ?? $newsArticle['summary'] ?? '';
$date = $newsArticle['date'] ?? date('Y-m-d H:i:s');
$sport = $newsArticle['activity_or_sport'] ?? 'High School Sports';
$humanDate = date('F j, Y', strtotime($date));

// === AUTO SPORT DETECT (fallback only) ===
$detectedSport = detect_sport_from_title($h1Title);
if ($detectedSport) {
    $sport = $detectedSport;
}

// === TREND MATCHING ===
if (!empty($sport)) {
    $trending = getTrendingKeywords();
    $matchedTrend = match_trend_with_sport($trending, $sport);

if ($matchedTrend) {
    $h1Title .= " | " . $matchedTrend;

    if (!empty($summary)) {
        $summary .= " Related trending topic: " . $matchedTrend . ".";
    } else {
        $summary = "Related trending topic: " . $matchedTrend . ".";
    }
}
}

// ✅ DYNAMIC UPCOMING EVENTS DATA FETCHING
function http_get_json_news(string $url, int $cacheTtl = 300): ?array {
    $cacheKey = md5($url);
    if (function_exists('cache_get')) {
        $cached = cache_get($cacheKey, $cacheTtl);
        if ($cached !== null && isset($cached['data'])) return $cached['data'];
    }
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: NFHS-News-Bot/1.0'],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response) return null;
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) return null;
    
    if (function_exists('cache_set')) cache_set($cacheKey, $decoded, $cacheTtl);
    return $decoded;
}



function fetch_upcoming_event_news(string $sport): ?array {
    $activity = strtolower(trim($sport));
    // Remove common prefixes for better API matching
    $activity = str_replace(['high school', 'varsity', 'junior', 'boys', 'girls'], '', $activity);
    $activity = trim($activity);
    
    // If activity is empty or too generic, don't filter by activity
    $useActivityFilter = !empty($activity) && $activity !== 'sports' && strlen($activity) > 2;
    
    $statuses = ['upcoming', 'live'];
    foreach ($statuses as $status) {
        $url = "https://search-api.nfhsnetwork.com/v3/search/events/{$status}?card=true&size=10";
        if ($useActivityFilter) {
            $url .= "&activity=" . urlencode($activity);
        }
        
        $data = http_get_json_news($url, 600);
        if (!empty($data['items'])) {
            // Find first event with valid team names
            foreach ($data['items'] as $ev) {
                $teams = extract_team_names($ev);
                if (!empty($teams['home']) || !empty($teams['away'])) {
                    // Extract team logos (first_logo = away, second_logo = home)
                    $homeLogo = $ev['second_logo'] ?? $ev['homeTeam']['logo'] ?? $ev['metadata']['home_logo'] ?? null;
                    $awayLogo = $ev['first_logo'] ?? $ev['awayTeam']['logo'] ?? $ev['metadata']['away_logo'] ?? null;
                    
                    // Extract background image - use card_image if available
                    $bgImage = $ev['card_image_url'] ?? $ev['thumbnail_url'] ?? $ev['image_url'] ?? '';
                    
                    // Get sport name from event
                    $eventSport = $ev['metadata']['activity_name'] ?? $ev['activity_or_sport'] ?? $sport;
                    
                    // Extract date - use 'date' field first (ISO format), then fallback
                    $eventDate = $ev['date'] ?? $ev['eventDate'] ?? $ev['startTime'] ?? null;
                    
                    // Extract location
                    $locationParts = array_filter([
                        $ev['venue']['name'] ?? null,
                        $ev['venue']['city'] ?? null,
                        $ev['venue']['state'] ?? $ev['state'] ?? null,
                    ]);
                    $location = !empty($locationParts) ? implode(', ', $locationParts) : null;
                    
                    return [
                        'key' => $ev['key'] ?? '',
                        'home' => $teams['home'],
                        'away' => $teams['away'],
                        'home_logo' => $homeLogo,
                        'away_logo' => $awayLogo,
                        'date' => $eventDate,
                        'location' => $location,
                        'sport' => $eventSport,
                        'thumbnail' => $bgImage
                    ];
                }
            }
        }
    }
    return null;
}

// ✅ NEW: Fetch multiple upcoming broadcasts for grid display (like sportsmediatv.site)
function fetch_upcoming_broadcasts(int $limit = 6): array {
    $broadcasts = [];
    
    $statuses = ['upcoming', 'live'];
    foreach ($statuses as $status) {
        $url = "https://search-api.nfhsnetwork.com/v3/search/events/{$status}?card=true&size=20";
        $data = http_get_json_news($url, 600);
        
        if (!empty($data['items'])) {
            foreach ($data['items'] as $ev) {
                if (count($broadcasts) >= $limit) break 2;
                
                // Get title - prefer formatted title
                $title = $ev['title'] ?? $ev['name'] ?? '';
                $teams = extract_team_names($ev);

                // STRICT VALIDATION: Skip if teams are missing or contain "TBD"
                if (empty($teams['home']) || empty($teams['away'])) continue;
                if (stripos($teams['home'], 'TBD') !== false || stripos($teams['away'], 'TBD') !== false) continue;
                if (stripos($teams['home'], 'Team TBD') !== false || stripos($teams['away'], 'Team TBD') !== false) continue;

                $title = $teams['home'] . ' vs. ' . $teams['away'];
                
                // Skip if no title
                if (empty($title)) continue;
                
                // Extract thumbnail - prioritize background_image (correct NFHS field)
                $thumbnail = $ev['background_image'] ?? $ev['card_image_url'] ?? $ev['thumbnail_url'] ?? $ev['image_url'] ?? '';
                
                // Get team logos (try multiple sources)
                $awayLogo = $ev['first_logo'] ?? $ev['awayTeam']['logo'] ?? '';
                $homeLogo = $ev['second_logo'] ?? $ev['homeTeam']['logo'] ?? '';
                
                // Fallback to participants array if available
                if (empty($homeLogo) && !empty($ev['participants'])) {
                    foreach ($ev['participants'] as $p) {
                        if (isset($p['is_home']) && $p['is_home'] && !empty($p['logo_url'])) $homeLogo = $p['logo_url'];
                        if (isset($p['is_home']) && !$p['is_home'] && !empty($p['logo_url'])) $awayLogo = $p['logo_url'];
                    }
                }
                
                // Get sport/activity name
                $sport = $ev['metadata']['activity_name'] ?? $ev['activity_or_sport'] ?? 'Sports';
                
                // Get date
                $eventDate = $ev['date'] ?? $ev['eventDate'] ?? $ev['startTime'] ?? null;
                $dateFormatted = $eventDate ? date('F j, Y g:i A', strtotime($eventDate)) : '';
                
                // Get state/location
                $state = $ev['venue']['state'] ?? $ev['state'] ?? '';
                
                $broadcasts[] = [
                    'key' => $ev['key'] ?? '',
                    'title' => $title,
                    'sport' => $sport,
                    'date' => $dateFormatted,
                    'thumbnail' => $thumbnail,
                    'state' => $state,
                    'home_logo' => $homeLogo,
                    'away_logo' => $awayLogo,
                ];
            }
        }
    }
    
    return $broadcasts;
}

// ✅ NEW: Smart Related Articles Logic
function get_related_articles(array $allArticles, array $currentArticle, int $limit = 5): array {
    $related = [];
    $pool = [];
    
    // Normalize current sport
    $currentSport = strtolower($currentArticle['activity_or_sport'] ?? '');
    if (empty($currentSport)) $currentSport = 'sports';
    
    // Split into tiers
    $tier1 = []; // Exact sport match
    $tier2 = []; // Partial sport match (e.g. "Girls Basketball" matches "Basketball")
    $tier3 = []; // Other articles
    
    foreach ($allArticles as $item) {
        // Skip current article
        if (($item['key'] ?? '') === ($currentArticle['key'] ?? '')) continue;
        
        $itemSport = strtolower($item['activity_or_sport'] ?? '');
        
        if ($itemSport === $currentSport) {
            $tier1[] = $item;
        } elseif (strpos($itemSport, $currentSport) !== false || strpos($currentSport, $itemSport) !== false) {
            $tier2[] = $item;
        } else {
            $tier3[] = $item;
        }
    }
    
    // Shuffle tiers for randomness
    shuffle($tier1);
    shuffle($tier2);
    shuffle($tier3);
    
    // Merge tiers: Priority 1 > 2 > 3
    $pool = array_merge($tier1, $tier2, $tier3);
    
    // Slice to limit
    return array_slice($pool, 0, $limit);
}

// ✅ SEO Optimization: Bold HIGH-SEARCH-VOLUME keywords and add anchor text links
function optimize_article_seo(string $content, string $sport, string $title, array $articleData = []): string {
    if (empty($content)) return $content;
    
    $potentialKeywords = [];
    $sportLower = strtolower(trim($sport));
    
    // ========== HIGH-SEARCH-VOLUME KEYWORDS ==========
    
    // 1. FULL SCHOOL NAMES (e.g., "Faith Christian High School basketball")
    // People search: "[School Name] [Sport]", "[School Name] scores"
    if (preg_match_all('/([A-Z][a-zA-Z\']+(?:\s+[A-Z][a-zA-Z\']+){1,4})\s+(?:High\s+School|Academy|Prep|School)/iu', $content, $schoolMatches)) {
        foreach ($schoolMatches[0] as $school) {
            $potentialKeywords[] = trim($school);
            $potentialKeywords[] = trim($school) . ' ' . $sportLower; // "Faith Christian High School basketball"
        }
    }
    
    // 2. FULL PLAYER NAMES (First + Last name only)
    // People search: "[Player Name] stats", "[Player Name] highlights"
    if (preg_match_all('/\b([A-Z][a-z]+)\s+([A-Z][a-z]+)\b(?=\s+(?:scored|had|led|recorded|hit|made|reached|broke|set))/u', $content, $playerMatches, PREG_SET_ORDER)) {
        foreach ($playerMatches as $match) {
            $fullName = $match[1] . ' ' . $match[2];
            if (strlen($fullName) > 5) {
                $potentialKeywords[] = $fullName;
            }
        }
    }
    
    // 3. RECORD-BREAKING TERMS (highly searched when records are broken)
    $recordTerms = [
        'national record', 'state record', 'school record', 'career record',
        'all-time record', 'single-game record', 'broke the record', 'set a new record',
        'record-breaking', 'historic', 'first in history'
    ];
    foreach ($recordTerms as $term) {
        if (stripos($content, $term) !== false) {
            $potentialKeywords[] = $term;
        }
    }
    
    // 4. STATE + SPORT COMBINATIONS (e.g., "California high school basketball")
    $states = ['California', 'Texas', 'Florida', 'New York', 'Ohio', 'Pennsylvania', 
               'Illinois', 'Georgia', 'North Carolina', 'Michigan', 'Arizona', 'Indiana',
               'Tennessee', 'Missouri', 'Wisconsin', 'Minnesota', 'Colorado', 'Alabama',
               'Louisiana', 'Kentucky', 'Oregon', 'Oklahoma', 'Connecticut', 'Iowa',
               'Mississippi', 'Arkansas', 'Kansas', 'Utah', 'Nevada', 'New Jersey'];
    
    foreach ($states as $state) {
        if (stripos($content, $state) !== false) {
            $potentialKeywords[] = $state . ' high school ' . $sportLower;
            $potentialKeywords[] = $state . ' ' . $sportLower;
            break;
        }
    }
    
    // 5. CHAMPIONSHIP/PLAYOFF TERMS (high search during season)
    $championshipTerms = [
        'state championship', 'state finals', 'state tournament', 'state playoffs',
        'section championship', 'regional championship', 'district championship',
        'championship game', 'playoff game', 'tournament game', 'title game'
    ];
    foreach ($championshipTerms as $term) {
        if (stripos($content, $term) !== false) {
            $potentialKeywords[] = $term;
        }
    }
    
    // 6. PERFORMANCE STATS (people search for standout performances)
    // e.g., "50 points", "10 3-pointers", "triple-double"
    if (preg_match_all('/(\d+\s+(?:points|rebounds|assists|touchdowns|goals|hits|strikeouts|saves|kills|aces|pins))/i', $content, $statMatches)) {
        foreach ($statMatches[1] as $stat) {
            if (intval($stat) >= 20) { // Only highlight impressive stats
                $potentialKeywords[] = $stat;
            }
        }
    }
    
    // 7. SPECIFIC SPORT TERMS that are searched
    $sportSearchTerms = [
        'basketball' => ['3-pointer', 'three-pointer', 'double-double', 'triple-double', 'buzzer beater'],
        'football' => ['touchdown pass', 'rushing yards', 'passing yards', 'quarterback', 'running back'],
        'baseball' => ['home run', 'no-hitter', 'perfect game', 'grand slam', 'strikeouts'],
        'soccer' => ['hat trick', 'penalty kick', 'clean sheet', 'golden goal'],
        'volleyball' => ['ace', 'kill', 'block', 'dig'],
    ];
    
    foreach ($sportSearchTerms as $sportKey => $terms) {
        if (stripos($sportLower, $sportKey) !== false) {
            foreach ($terms as $term) {
                if (stripos($content, $term) !== false) {
                    $potentialKeywords[] = $term;
                }
            }
            break;
        }
    }
    
    // Remove duplicates and sort by length (longer first)
    $potentialKeywords = array_unique($potentialKeywords);
    usort($potentialKeywords, function($a, $b) { return strlen($b) - strlen($a); });
    
    // ========== BOLD TOP KEYWORDS (max 3) ==========
    $boldedCount = 0;
    $maxBold = 3;
    $processedKeywords = [];
    
    foreach ($potentialKeywords as $keyword) {
        if ($boldedCount >= $maxBold) break;
        if (strlen($keyword) < 4) continue;
        
        // Skip keywords that are mostly numbers (statistics)
        if (preg_match('/^\d+[\s\-\d]+$/', $keyword)) continue;

        $keyLower = strtolower($keyword);
        if (isset($processedKeywords[$keyLower])) continue;
        
        $pattern = '/\b(' . preg_quote($keyword, '/') . ')\b/iu';
        if (preg_match($pattern, $content) && stripos($content, '<strong>' . $keyword) === false) {
            $content = preg_replace($pattern, '<strong>$1</strong>', $content, 1);
            $processedKeywords[$keyLower] = true;
            $boldedCount++;
        }
    }
    
    // ========== ADD ANCHOR LINKS (max 1-2) ==========
    $sportSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $sport));
    $anchorLinks = [
        $sportLower => SITE_PATH . '?sport=' . urlencode($sportSlug),
        'high school ' . $sportLower => SITE_PATH . '?sport=' . urlencode($sportSlug),
    ];
    
    $linksAdded = 0;
    foreach ($anchorLinks as $term => $url) {
        if ($linksAdded >= 1) break; // Reduced to max 1 for cleaner look
        if (stripos($content, '<strong>' . $term . '</strong>') !== false) {
            $replacement = '<a href="' . htmlspecialchars($url) . '" class="seo-anchor"><strong>' . $term . '</strong></a>';
            $content = preg_replace('/<strong>(' . preg_quote($term, '/') . ')<\/strong>/i', $replacement, $content, 1);
            $linksAdded++;
        }
    }
    
    return $content;
}



$upcomingEvent = fetch_upcoming_event_news($sport);
$eventRedirect = '';
if ($upcomingEvent && !empty($upcomingEvent['key'])) {
    $eventRedirect = SITE_PATH . "player.php?event=" . urlencode($upcomingEvent['key']);
}

// Fetch multiple upcoming broadcasts for grid display
$upcomingBroadcasts = fetch_upcoming_broadcasts(6);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($h1Title); ?> | MaxPreps News</title>
    <?php
    $keywords = $sport . ", high school sports, " . implode(", ", explode(" ", $h1Title));
    ?>
    <meta name="description" content="<?php echo htmlspecialchars($summary); ?>">
    <link rel="icon" href="<?php echo SITE_PATH; ?>favicon.ico" type="image/x-icon">
    <meta name="keywords" content="<?php echo htmlspecialchars($keywords); ?>">
    <link rel="canonical" href="<?= base_origin() . $_SERVER['REQUEST_URI'] ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
    
    <!-- Fonts & CSS -->
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/common.css?v=<?php echo get_asset_version('assets/css/common.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/news.css?v=<?php echo get_asset_version('assets/css/news.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/home.css?v=<?php echo get_asset_version('assets/css/home.css'); ?>">

    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="MaxPreps News">
    <meta property="og:title" content="<?php echo htmlspecialchars($h1Title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($summary); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($heroImage); ?>">
    <meta property="og:url" content="<?= base_origin() . $_SERVER['REQUEST_URI'] ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($h1Title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($summary); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($heroImage); ?>">

    <!-- JSON-LD Structured Data for SEO -->
    <script type="application/ld+json">
    <?php
    $newsArticleSchema = [
        "@context" => "https://schema.org",
        "@type" => "NewsArticle",
        "headline" => $h1Title,
        "image" => [$heroImage],
        "datePublished" => date('c', strtotime($date)),
        "dateModified" => date('c', strtotime($date)),
        "author" => [[
            "@type" => "Person",
            "name" => "John Smith",
            "url" => base_origin() . SITE_PATH . "author/john-smith"
        ]],
        "publisher" => [
            "@type" => "Organization",
            "name" => $config['site_title'] ?? 'MaxPreps News',
            "logo" => [
                "@type" => "ImageObject",
                "url" => "https://social.nfhsnetwork.com/default_share.png"
            ]
        ],
        "description" => $summary
    ];
    echo json_encode($newsArticleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    ?>
    </script>
    <?php if (!empty($newsArticle['videos'])): ?>
    <script type="application/ld+json">
    <?php
    $videoSchema = [
        "@context" => "https://schema.org",
        "@type" => "VideoObject",
        "name" => $h1Title,
        "description" => $summary,
        "thumbnailUrl" => [$heroImage],
        "uploadDate" => date('c', strtotime($date)),
        "contentUrl" => base_origin() . SITE_PATH . "assets/stream.mp4",
        "embedUrl" => base_origin() . SITE_PATH . "embed.php?event=" . urlencode($newsId),
        "interactionStatistic" => [
            "@type" => "InteractionCounter",
            "interactionType" => ["@type" => "WatchAction"],
            "userInteractionCount" => 1234
        ]
    ];
    echo json_encode($videoSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    ?>
    </script>
    <?php endif; ?>
    <script type="application/ld+json">
    <?php
    $breadcrumbSchema = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => [
            [
                "@type" => "ListItem",
                "position" => 1,
                "name" => "Home",
                "item" => base_origin() . SITE_PATH
            ],
            [
                "@type" => "ListItem",
                "position" => 2,
                "name" => $sport,
                "item" => base_origin() . SITE_PATH . "?sport=" . urlencode(strtolower(str_replace(' ', '-', $sport)))
            ],
            [
                "@type" => "ListItem",
                "position" => 3,
                "name" => $h1Title,
                "item" => base_origin() . $_SERVER['REQUEST_URI']
            ]
        ]
    ];
    echo json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    ?>
    </script>
</head>
<body>
<?php include __DIR__ . '/templates/header.php'; ?>

    <div class="page-wrapper">
        <main>
            <article class="article-card">
                <header class="article-header-box">
                    <span class="category-tag"><?php echo htmlspecialchars($sport); ?></span>
                    <h1 class="article-title"><?php echo htmlspecialchars($h1Title); ?></h1>
                    
                    <div class="article-meta-row">
                        <div class="author-pill">By <a href="<?php echo SITE_PATH; ?>author/john-smith">John Smith</a></div>
                        <time datetime="<?php echo date('c', strtotime($date)); ?>">
                            <?php echo htmlspecialchars($humanDate); ?>
                        </time>
                    </div>

                    <!-- Social Share Buttons -->
                    <div class="social-share-container" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #e9ecef;">
                        <div class="social-share-label" style="font-size:0.8rem; margin-bottom:10px;">Share this article:</div>
                        <div class="social-share-buttons">
                            <a href="#" class="social-share-btn social-share-facebook" id="shareFacebook" aria-label="Share on Facebook" target="_blank" rel="noopener noreferrer">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                <span>Facebook</span>
                            </a>
                            <a href="#" class="social-share-btn social-share-twitter" id="shareTwitter" aria-label="Share on X (Twitter)" target="_blank" rel="noopener noreferrer">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                                <span>X</span>
                            </a>
                            <a href="#" class="social-share-btn social-share-whatsapp" id="shareWhatsApp" aria-label="Share on WhatsApp" target="_blank" rel="noopener noreferrer">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                </svg>
                                <span>WhatsApp</span>
                            </a>
                            <a href="#" class="social-share-btn social-share-telegram" id="shareTelegram" aria-label="Share on Telegram" target="_blank" rel="noopener noreferrer">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.559z"/>
                                </svg>
                                <span>Telegram</span>
                            </a>
                            <button class="social-share-btn social-share-copy" id="shareCopy" aria-label="Copy link">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                                </svg>
                                <span>Copy Link</span>
                            </button>
                        </div>
                    </div>
                </header>

                
                <section class="article-body-content">
                    <?php 
                    if (!empty($newsArticle['content'])) {
                        $content = $newsArticle['content'];
                        
                        // ✅ FIX: Resolve any internal images in the article content
                        // Replace <img src="/images/..." with <img src="SITE_PATH/images/..."
                        // We use a regex to find src attributes starting with / and not followed by another / (external protocol)
                        $content = preg_replace_callback('/<img([^>]+)src=["\']\/([^\/][^"\']*)["\']/', function($matches) {
                            $path = $matches[2];
                            $newSrc = SITE_PATH . $path;
                            return '<img' . $matches[1] . 'src="' . $newSrc . '"';
                        }, $content);
                        
                        // Prepare Video Player HTML if videos exist
                        
                        // Inject Advertisement from Config
                        if (!empty($config['ads']['article_middle'])) {
                            // Simple injection: Find the 3rd paragraph closing tag and append ad
                            $adHtml = '<div class="ad-container article-ad" style="text-align:center; margin: 30px 0;">' . $config['ads']['article_middle'] . '</div>';
                            $paragraphs = explode('</p>', $content);
                            if (count($paragraphs) > 3) {
                                $paragraphs[2] .= $adHtml;
                                $content = implode('</p>', $paragraphs);
                            } else {
                                $content .= $adHtml;
                            }
                        }
                        if (!empty($newsArticle['videos'])) {
                            $video = $newsArticle['videos'][0];
                            $hlsUrl = $video['hls_url'] ?? '';
                            $videoPoster = resolve_image_url(!empty($video['poster']) ? $video['poster'] : (!empty($newsArticle['hero_image']) ? $newsArticle['hero_image'] : ($newsArticle['hero_image_url'] ?? $heroImage)));
                            
                            $videoHtml = '
                            <div class="video-player-wrapper" style="margin: 30px 0;">
                                <div class="mt-video-player video-poster-overlay" 
                                     data-hls="'.htmlspecialchars($hlsUrl).'" 
                                     data-poster="'.htmlspecialchars($videoPoster).'"
                                     style="background-image: url(\''.htmlspecialchars($videoPoster).'\');">
                                    <div class="play-icon-btn">
                                        <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                            </div>';
                            
                            // Replace placeholder or prepend if not found
                            if (strpos($content, '{{VIDEO_PLAYER}}') !== false) {
                                $content = str_replace('{{VIDEO_PLAYER}}', $videoHtml, $content);
                            } else {
                                $content = $videoHtml . $content;
                            }
                        }
                        
                        // ✅ Apply SEO optimization: bold keywords & add anchor links
                        $content = optimize_article_seo($content, $sport, $h1Title);
                        
                        echo $content;
                    } else { ?>
                        <p style="font-weight:700; font-size:1.25rem;">
                            <?php echo htmlspecialchars($summary); ?>
                        </p>
                        <p>
                            Exclusive coverage of this high school sports event. 
                            <strong><?php echo htmlspecialchars($h1Title); ?></strong> continues to draw widespread attention with high-stakes performances and remarkable athleticism. 
                        </p>
                        <p>
                            Fans from across the region gathered to witness the action, and the atmosphere delivered on every expectation. 
                            Stay tuned for further analysis, expert highlights, and verified results from this matchup as the season progresses.
                        </p>
                    <?php } ?>
                    
                    
                    <div class="editorial-disclaimer">
                        <strong>Editorial Note:</strong> This report is based on verified game data and official statistics.
                    </div>

                    <div class="related-content-section">
                        <h3>Related High School Sports News</h3>
                        
                        <ul>
                        <?php
                            foreach (array_slice($items, 0, 5) as $related) {
                                if ($related['key'] !== $newsId) {
                                    $title = $related['headline'] ?? $related['title'] ?? 'news';
                                    
                                    $fallbackSlug = strtolower(str_replace(' ', '-', $title));
                                    
                                    $slug = isset($related['slug']) && !empty($related['slug']) ? $related['slug'] : $fallbackSlug; 
                                    
                                    echo '<li><a href="' . SITE_PATH . 'news/' . $slug . '-' . $related['key'] . '">' . htmlspecialchars($related['title']) . '</a></li>';
                                  }
                            }
                         ?>
                        </ul>

                    <!-- ✅ NEW: Upcoming Broadcasts Grid (like sportsmediatv.site) -->
                    <?php if (!empty($upcomingBroadcasts)): ?>
                    <div class="broadcasts-container">
                        <h3 class="broadcasts-section-title">Upcoming Broadcasts</h3>
                        <div class="broadcasts-grid">
                            <?php foreach ($upcomingBroadcasts as $broadcast): ?>
                            <a href="<?php echo SITE_PATH; ?>player.php?event=<?php echo urlencode($broadcast['key']); ?>" class="broadcast-card">
                                <div class="broadcast-thumbnail" style="<?php if ($broadcast['thumbnail']) echo 'background-image: url(\'' . htmlspecialchars($broadcast['thumbnail']) . '\');'; ?>">
                                    <!-- Team logos overlay -->
                                    <?php if ($broadcast['home_logo'] || $broadcast['away_logo']): ?>
                                    <div class="broadcast-logos">
                                        <?php if ($broadcast['home_logo']): ?>
                                        <div class="bc-logo"><img src="<?php echo htmlspecialchars($broadcast['home_logo']); ?>" alt="Home" onerror="this.style.display='none'" loading="lazy"></div>
                                        <?php endif; ?>
                                        <span class="bc-vs">VS</span>
                                        <?php if ($broadcast['away_logo']): ?>
                                        <div class="bc-logo"><img src="<?php echo htmlspecialchars($broadcast['away_logo']); ?>" alt="Away" onerror="this.style.display='none'" loading="lazy"></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="broadcast-info">
                                    <span class="broadcast-sport"><?php echo htmlspecialchars(strtoupper($broadcast['sport'])); ?></span>
                                    <h4 class="broadcast-title"><?php echo htmlspecialchars($broadcast['title']); ?></h4>
                                    <?php if ($broadcast['date']): ?>
                                    <span class="broadcast-date">📅 <?php echo htmlspecialchars($broadcast['date']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                        <!-- 2. Advertisement -->
                        <div class="inline-ad-banner">
                            <div class="ad-label">
                                <script>
                                (function(dte){
                                var d = document,
                                s = d.createElement('script'),
                                l = d.scripts[d.scripts.length - 1];
                                s.settings = dte || {};
                                s.src = '//stale-father.com/bcXtVYs.dTGml/0/YSWFcx/-e/mt9yuZZ/UJlHkrPhTfYo4tM/zLMtxlOKTaMatINPjXgczjMAzsEb5SNHwE';
                                s.async = true;
                                s.referrerPolicy = 'no-referrer-when-downgrade';
                                l.parentNode.insertBefore(s, l);
                                })({})
                                </script>
                                </div>
                        </div>

                        <!-- 3. Related News (2 items) -->
                        <div class="footer-news-grid">
                            <?php 
                            $footerNews = [];
                            if (isset($allItems) || isset($items)) {
                                $source = $allItems ?? $items ?? [];
                                $footerNews = get_related_articles($source, $newsArticle, 2);
                            }

                                foreach ($footerNews as $news): 
                                    $title = $news['headline'] ?? $news['title'] ?? 'News';
                                    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
                                    $slug = trim($slug, '-');
                                    $nUrl = SITE_PATH . "news/" . $slug . "-" . $news['key'];
                                    $nThumb = resolve_image_url($news['hero_image'] ?? $news['thumbnail'] ?? '');
                                ?>
                                    <a href="<?php echo $nUrl; ?>" class="footer-news-item">
                                        <div class="footer-news-thumb" style="background-image: url('<?php echo htmlspecialchars($nThumb); ?>');"></div>
                                        <div class="footer-news-info">
                                            <span class="footer-news-tag">News</span>
                                            <h4 class="footer-news-headline"><?php echo htmlspecialchars($title); ?></h4>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                        </div>

                        <!-- 4. Advertisement -->
                        <div class="inline-ad-banner">
                            <div class="ad-label">
                                 <script>
                                (function(dte){
                                var d = document,
                                s = d.createElement('script'),
                                l = d.scripts[d.scripts.length - 1];
                                s.settings = dte || {};
                                s.src = '//stale-father.com/bcXtVYs.dTGml/0/YSWFcx/-e/mt9yuZZ/UJlHkrPhTfYo4tM/zLMtxlOKTaMatINPjXgczjMAzsEb5SNHwE';
                                s.async = true;
                                s.referrerPolicy = 'no-referrer-when-downgrade';
                                l.parentNode.insertBefore(s, l);
                                })({})
                                </script>
                                </div>
                        </div>
                    </div>
                </section>
            </article>
        </main>

        <aside class="sidebar">
            <section class="sidebar-section">
                <h2 class="sidebar-title">Related Articles</h2>
                <div class="sidebar-articles-feed">
                    <?php 
                    $sidebarArticles = [];
                    if (isset($allItems) || isset($items)) {
                        $source = $allItems ?? $items ?? [];
                        // Used 2 for footer, get 5 distinct ones for sidebar if possible
                        // But get_related_articles handles prioritization well, just requesting 5
                        // Note: shuffling in function ensures we might get different ones if pool is large enough
                        // To be safe, we can request 7 and skip those in footerNews, or just request 5 and rely on randomness
                        
                        // Let's get 8, filter out footer news, then take 5
                        $potentialSidebar = get_related_articles($source, $newsArticle, 10);
                        
                        $footerKeys = array_map(function($i) { return $i['key']; }, $footerNews);
                        
                        foreach ($potentialSidebar as $pItem) {
                            if (!in_array($pItem['key'], $footerKeys)) {
                                $sidebarArticles[] = $pItem;
                            }
                            if (count($sidebarArticles) >= 5) break; 
                        }
                    }

                    foreach ($sidebarArticles as $side): 
                        $sideTitle = $side['headline'] ?? $side['title'];
                        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $sideTitle));
                        $slug = trim($slug, '-');
                        $sideUrl = SITE_PATH . "news/" . $slug . "-" . $side['key'];
                        $sideThumb = resolve_image_url($side['hero_image'] ?? $side['thumbnail'] ?? '');
                    ?>
                        <a href="<?php echo $sideUrl; ?>" class="sidebar-article-item">
                            <?php if ($sideThumb): ?>
                                <div class="side-thumb" style="background-image: url('<?php echo htmlspecialchars($sideThumb); ?>');"></div>
                            <?php endif; ?>
                            <div class="side-info">
                                <h4 class="side-headline"><?php echo htmlspecialchars($sideTitle); ?></h4>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="ad-placeholder sidebar-ad">
                    <span>
                                <script>
                                (function(dte){
                                var d = document,
                                s = d.createElement('script'),
                                l = d.scripts[d.scripts.length - 1];
                                s.settings = dte || {};
                                s.src = '//stale-father.com/bcXtVYs.dTGml/0/YSWFcx/-e/mt9yuZZ/UJlHkrPhTfYo4tM/zLMtxlOKTaMatINPjXgczjMAzsEb5SNHwE';
                                s.async = true;
                                s.referrerPolicy = 'no-referrer-when-downgrade';
                                l.parentNode.insertBefore(s, l);
                                })({})
                                </script>
                    </span>
                </div>
            </section>
        </aside>
    </div>

    <?php include __DIR__ . '/templates/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const players = document.querySelectorAll('.mt-video-player');
            players.forEach(player => {
                player.addEventListener('click', function() {
                    let hlsUrl = this.getAttribute('data-hls');
                    const poster = this.getAttribute('data-poster');
                    
                    <?php 
                        $hlsDomain = parse_url($config['maxpreps_image_url'] ?? 'https://image.maxpreps.io', PHP_URL_HOST);
                    ?>
                    if (hlsUrl && hlsUrl.includes('<?php echo $hlsDomain; ?>')) {
                        hlsUrl = '<?php echo base_origin() . SITE_PATH; ?>proxy_hls.php?url=' + encodeURIComponent(hlsUrl);
                    }
                    
                    const video = document.createElement('video');
                    video.className = 'active-video-element';
                    video.controls = true;
                    video.autoplay = true;
                    video.style.width = '100%';
                    video.style.height = '100%';
                    video.style.objectFit = 'contain';
                    if (poster) video.poster = poster;
                    
                    this.innerHTML = '';
                    this.appendChild(video);
                    this.classList.remove('video-poster-overlay');
                    this.style.backgroundImage = 'none';

                    if (Hls.isSupported()) {
                        const hls = new Hls();
                        hls.loadSource(hlsUrl);
                        hls.attachMedia(video);
                        hls.on(Hls.Events.MANIFEST_PARSED, function() {
                            video.play().catch(e => console.error("Play failed:", e));
                        });
                        hls.on(Hls.Events.ERROR, function (event, data) {
                            console.error("HLS Error:", data.type, data.details, data.fatal);
                            if (data.fatal) {
                                switch (data.type) {
                                    case Hls.ErrorTypes.NETWORK_ERROR:
                                        console.error("Network error, trying to recover...");
                                        hls.startLoad();
                                        break;
                                    case Hls.ErrorTypes.MEDIA_ERROR:
                                        console.error("Media error, trying to recover...");
                                        hls.recoverMediaError();
                                        break;
                                    default:
                                        hls.destroy();
                                        break;
                                }
                            }
                        });
                    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                        video.src = hlsUrl;
                        video.addEventListener('loadedmetadata', function() {
                            video.play().catch(e => console.error("Native play failed:", e));
                        });
                    }
                }, { once: true });
            });
        });

        (function() {
            // Share configuration
            const pageUrl = window.location.href;
            const title = "<?php echo addslashes($h1Title); ?>";
            const shareTemplateWithUrl = title + " " + pageUrl;
            const shareTemplateWithoutUrl = title;

            // Get share buttons
            const shareFacebook = document.getElementById('shareFacebook');
            const shareTwitter = document.getElementById('shareTwitter');
            const shareWhatsApp = document.getElementById('shareWhatsApp');
            const shareTelegram = document.getElementById('shareTelegram');
            const shareCopy = document.getElementById('shareCopy');

            // Facebook Share
            if (shareFacebook) {
                shareFacebook.addEventListener('click', async (e) => {
                    e.preventDefault();
                    try {
                        const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(pageUrl)}`;
                        window.open(url, '_blank');
                    } catch (err) {
                        console.error('Failed to share:', err);
                    }
                });
            }

            // Twitter/X Share
            if (shareTwitter) {
                shareTwitter.addEventListener('click', (e) => {
                    e.preventDefault();
                    const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareTemplateWithUrl)}`;
                    window.open(url, '_blank');
                });
            }

            // WhatsApp Share
            if (shareWhatsApp) {
                shareWhatsApp.addEventListener('click', (e) => {
                    e.preventDefault();
                    const url = `https://wa.me/?text=${encodeURIComponent(shareTemplateWithUrl)}`;
                    window.open(url, '_blank');
                });
            }

            // Telegram Share
            if (shareTelegram) {
                shareTelegram.addEventListener('click', (e) => {
                    e.preventDefault();
                    const url = `https://t.me/share/url?url=${encodeURIComponent(pageUrl)}&text=${encodeURIComponent(shareTemplateWithoutUrl)}`;
                    window.open(url, '_blank');
                });
            }

            // Copy Link
            if (shareCopy) {
                shareCopy.addEventListener('click', async (e) => {
                    e.preventDefault();
                    try {
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(shareTemplateWithUrl);
                        } else {
                            const textArea = document.createElement('textarea');
                            textArea.value = shareTemplateWithUrl;
                            document.body.appendChild(textArea);
                            textArea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textArea);
                        }
                        
                        shareCopy.classList.add('copied');
                        setTimeout(() => {
                            shareCopy.classList.remove('copied');
                        }, 2000);
                    } catch (err) {
                        console.error('Failed to copy link:', err);
                        alert('Failed to copy link.');
                    }
                });
            }
        })();
    </script>
</body>
</html>
