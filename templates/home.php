<?php
// templates/home.php
// Homepage layout for SportsMediaTV concept

// Ensure we have the search function available (from index.php)
if (!function_exists('http_get_json')) {
    // This should not happen if included from index.php
    die('Direct access not allowed');
}

// Fetch content for the homepage
// 1. Live Events (Breaking News)
$liveUrl = 'https://search-api.nfhsnetwork.com/v3/search/events/live?card=true&size=4';
$liveData = http_get_json($liveUrl, 60); // Short cache for live
$liveEvents = $liveData['items'] ?? [];

// 2. Upcoming Events
$upcomingUrl = 'https://search-api.nfhsnetwork.com/v3/search/events/upcoming?card=true&size=100'; 
$upcomingData = http_get_json($upcomingUrl, 300); 
$upcomingEvents = $upcomingData['items'] ?? [];

// 2b. On Demand Events (for TRENDING NOW sidebar)
$onDemandSidebarUrl = 'https://search-api.nfhsnetwork.com/v3/search/events/ondemand?card=true&size=10';
$onDemandSidebarData = http_get_json($onDemandSidebarUrl, 300);
$onDemandSidebarEvents = $onDemandSidebarData['items'] ?? [];

// 3. MaxPreps News (Auto-Updated)
// Using module from cron/fetch_news.php
require_once __DIR__ . '/../cron/fetch_news.php';
$mpData = fetchMaxPrepsNews();
$newsEvents = $mpData['items'] ?? [];

// Fallback to On Demand if MaxPreps empty
if (empty($newsEvents)) {
    $onDemandUrl = 'https://search-api.nfhsnetwork.com/v3/search/events/ondemand?card=true&size=10';
    $onDemandData = http_get_json($onDemandUrl, 300);
    $newsEvents = $onDemandData['items'] ?? [];
}

// Combine for display if live is empty - REMOVED to prevent upcoming overtaking on-demand
// if (empty($liveEvents)) { ... }

// ✅ Disable randomization to show LATEST news first
/*
if (!empty($newsEvents)) {
    shuffle($newsEvents);
}
*/
if (!empty($upcomingEvents)) {
    shuffle($upcomingEvents);
}

// Helper to format date
function get_friendly_date($isoDate) {
    if (!$isoDate) return 'TBA';
    try {
        $dt = new DateTime($isoDate);
        return $dt->format('F j, Y g:i A');
    } catch (Exception $e) {
        return 'TBA';
    }
}

// ✅ PREPARE META DATA (OG TAGS)
// Determine Featured Event early to use for OG Image
$featured = $onDemandSidebarEvents[0] ?? $liveEvents[0] ?? $upcomingEvents[0] ?? null;
$featuredSource = '';
if ($featured === ($onDemandSidebarEvents[0] ?? null)) $featuredSource = 'ondemand';
elseif ($featured === ($liveEvents[0] ?? null)) $featuredSource = 'live';
elseif ($featured === ($upcomingEvents[0] ?? null)) $featuredSource = 'upcoming';

$ogImage = $featured['background_image'] ?? $featured['hero_image'] ?? $featured['thumbnail'] ?? $featured['image'] ?? 'https://social.nfhsnetwork.com/default_share.png';
$ogTitle = ($config['site_title'] ?? 'HighSchoolNews') . ' - High School Sports News & Verified Streams';
$ogDesc = "Latest high school sports news, live streams, and scores. Watch verified streams of your favorite teams.";
$pageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($ogTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($ogDesc); ?>">
    <meta name="google-adsense-account" content="ca-pub-8973762345950558">
    <meta name="referrer" content="no-referrer-when-downgrade" />
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($pageUrl); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($ogTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($ogDesc); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo htmlspecialchars($pageUrl); ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($ogTitle); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($ogDesc); ?>">
    <meta property="twitter:image" content="<?php echo htmlspecialchars($ogImage); ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Merriweather:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/common.css?v=<?php echo get_asset_version('assets/css/common.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/home.css?v=<?php echo get_asset_version('assets/css/home.css'); ?>">
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>

<!-- Ticker -->
<?php if (!empty($liveEvents)): ?>
<div class="news-ticker">
    <div class="ticker-content">
        <?php foreach($liveEvents as $ev): 
            $title = $ev['headline'] ?? $ev['title'] ?? 'Live Event';
        ?>
            <span class="ticker-item"><span class="live-badge">LIVE</span> <?php echo htmlspecialchars($title); ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<main class="container">
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-grid">
            <!-- Main Featured Story (First On-Demand, then Live, then Upcoming) -->
            <?php 
            // Featured event logic moved to top for OG tags (see line ~63)
            
            if ($featured):
                $featImg = resolve_image_url($featured['background_image'] ?? $featured['hero_image'] ?? $featured['thumbnail'] ?? $featured['image'] ?? '');
                
                $featTitle = $featured['headline'] ?? $featured['title'] ?? $featured['name'] ?? $featured['first_title'] ?? 'Featured Event';
                
                // FORCE REWRITE for NFHS sources if team names exist
                // This ensures we always show "Team A vs. Team B" instead of generic titles
                if (!isset($featured['source']) || ($featured['source'] !== 'MaxPreps' && $featured['source'] !== 'Yahoo Sports')) {
                    $teams = extract_team_names($featured);
                    if (!empty($teams['home']) && !empty($teams['away'])) {
                        // Check for TBD
                        if (stripos($teams['home'], 'TBD') === false && stripos($teams['away'], 'TBD') === false) {
                            $featTitle = $teams['home'] . ' vs ' . $teams['away'];
                        }
                    }
                }

                $featId = $featured['key'] ?? '';
                $featDate = get_friendly_date($featured['date'] ?? $featured['eventDate'] ?? $featured['startTime'] ?? $featured['publishDate'] ?? null);
                
                // Determine if Replay
                $isReplay = ($featuredSource === 'ondemand') || (isset($featured['status']) && $featured['status'] === 'ondemand') || isset($featured['vod']);
                
                $featLabel = $isReplay ? 'Watch Replay' : 'Watch Live Stream';
                $featAction = $isReplay ? 'Relive the moments' : "Don't miss the action";
                $featLink = "player.php?event={$featId}";
                $defaultImg = resolve_image_url(''); // Get the default thumbnail
            ?>
            <div class="main-story">
                <a href="<?php echo $featLink; ?>" style="position:relative; display:block;">
                    <img src="<?php echo $featImg; ?>" alt="<?php echo htmlspecialchars($featTitle); ?>" loading="lazy" onerror="this.src='<?php echo $defaultImg; ?>'">
                    <?php if ($isReplay): ?>
                        <span class="replay-badge" style="font-size:12px; padding:4px 8px; bottom:10px; right:10px;">REPLAY</span>
                    <?php endif; ?>
                </a>
                <div class="main-story-content">
                    <span class="category-tag"><?php echo htmlspecialchars($featured['activity_or_sport'] ?? $featured['sport'] ?? 'Sports'); ?></span>
                    <h1 class="main-story-title">
                        <a href="<?php echo $featLink; ?>"><?php echo htmlspecialchars($featTitle); ?></a>
                    </h1>
                    <p class="main-story-excerpt">
                        <?php echo $featLabel; ?> of <?php echo htmlspecialchars($featTitle); ?>. 
                        Coverage begins <?php echo $featDate; ?>. <?php echo $featAction; ?>.
                    </p>
                </div>

                <!-- Social Share Buttons (User Request) -->
                <div class="social-share-container" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #e9ecef;">
                    <div class="social-share-label" style="font-size:0.8rem; margin-bottom:10px;">Share this page:</div>
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
            </div>
            <?php endif; ?>

            <!-- Sidebar Top Stories -->
            <div class="top-stories-sidebar">
                
                <?php if (!empty($config['ads']['sidebar_top'])): ?>
                    <div class="ad-container sidebar-ad" style="text-align:center; margin-bottom: 20px;">
                        <?php echo $config['ads']['sidebar_top']; ?>
                    </div>
                <?php endif; ?>
                <h3 class="sidebar-heading">Trending Now</h3>
                <?php 
                // ✅ Sidebar Logic: Avoid duplicating the Featured event
                $sidebarEvents = [];
                
                // Get source arrays
                $sourceLive = $liveEvents;
                $sourceOnDemand = $onDemandSidebarEvents;
                
                // Remove featured from source arrays if present
                if ($featuredSource === 'live') array_shift($sourceLive);
                if ($featuredSource === 'ondemand') array_shift($sourceOnDemand);
                
                // Fill sidebar: First try On-Demand, then Live
                // We want 3 items total
                $sidebarEvents = array_slice($sourceOnDemand, 0, 3);
                
                // If not enough, fill with Live
                if (count($sidebarEvents) < 3) {
                    $need = 3 - count($sidebarEvents);
                    $sidebarEvents = array_merge($sidebarEvents, array_slice($sourceLive, 0, $need));
                }
                
                // If STILL not enough, usage Upcoming
                if (count($sidebarEvents) < 3) {
                    $need = 3 - count($sidebarEvents);
                    $sidebarEvents = array_merge($sidebarEvents, array_slice($upcomingEvents, ($featuredSource === 'upcoming' ? 1 : 0), $need));
                }
                
                foreach($sidebarEvents as $ev):
                    $img = resolve_image_url($ev['background_image'] ?? $ev['hero_image'] ?? $ev['thumbnail'] ?? $ev['image'] ?? '');
                    
                    // ✅ Better title extraction
                    $title = $ev['headline'] ?? $ev['title'] ?? $ev['name'] ?? $ev['first_title'] ?? 'Event';
                    
                    // Optimization: Force rewrite for NFHS sources to ensure Team vs Team format
                    if (!isset($ev['source']) || $ev['source'] !== 'MaxPreps') {
                        $teams = extract_team_names($ev);
                        if (!empty($teams['home']) && !empty($teams['away'])) {
                            // Check for TBD
                            if (stripos($teams['home'], 'TBD') === false && stripos($teams['away'], 'TBD') === false) {
                                $title = $teams['home'] . ' vs ' . $teams['away'];
                            }
                        }
                    }
                    
                    $id = $ev['key'] ?? '';
                    $time = get_friendly_date($ev['date'] ?? $ev['eventDate'] ?? $ev['startTime'] ?? $ev['publishDate'] ?? null);
                    $sport = $ev['activity_or_sport'] ?? $ev['activity'] ?? $ev['sport'] ?? 'Replay';
                ?>
                <div class="small-story">
                    <a href="player.php?event=<?php echo $id; ?>">
                        <img src="<?php echo $img; ?>" alt="" loading="lazy">
                        <span class="replay-badge">REPLAY</span>
                    </a>
                    <div>
                        <h4 class="small-story-title">
                            <a href="player.php?event=<?php echo $id; ?>"><?php echo htmlspecialchars($title); ?></a>
                        </h4>
                        <span class="time-label"><?php echo $time; ?> • <span style="color:#c00;"><?php echo htmlspecialchars($sport); ?></span></span>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (!empty($config['ads']['sidebar_bottom'])): ?>
                <div class="ad-container sidebar-ad" style="text-align:center; margin-top: 30px;">
                    <?php echo $config['ads']['sidebar_bottom']; ?>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </section>

    <!-- Latest News (On Demand) List -->
    <?php if (!empty($newsEvents)): ?>
    <section class="news-section" id="news">
        <div class="section-header">
            <h2 class="section-title">Latest Highlight Articles</h2>
        </div>
        
        <div class="news-list">
            <?php 
            // Pagination Logic
            $itemsPerPage = 15;
            $totalItems = count($newsEvents);
            $totalPages = ceil($totalItems / $itemsPerPage);
            $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            
            // Ensure current page is valid
            if ($currentPage > $totalPages) $currentPage = $totalPages;
            if ($currentPage < 1) $currentPage = 1; // Ensure page is at least 1
            
            $offset = ($currentPage - 1) * $itemsPerPage;
            $paginatedEvents = array_slice($newsEvents, $offset, $itemsPerPage);
            $loopCount = 0;
            
            foreach($paginatedEvents as $ev): 
                $loopCount++;
                $img = resolve_image_url($ev['background_image'] ?? $ev['hero_image'] ?? $ev['thumbnail'] ?? '');
                $title = $ev['headline'] ?? $ev['title'] ?? 'Event';
                
                // Optimization: Use shared helper to reconstruct title (NFHS ONLY)
                if (!isset($ev['source']) || ($ev['source'] !== 'MaxPreps' && $ev['source'] !== 'Yahoo Sports')) {
                    $teams = extract_team_names($ev);
                    if (!empty($teams['home']) && !empty($teams['away'])) {
                         $title = $teams['home'] . ' vs ' . $teams['away'];
                    }
                }
                $id = $ev['key'] ?? '';
                $sport = $ev['activity_or_sport'] ?? 'HS Sports';
                $time = get_friendly_date($ev['date'] ?? $ev['startTime'] ?? null);
                $desc = $ev['summary'] ?? $ev['description'] ?? 'Relive the action from ' . $title . '.';

                // Determine Link Type
                $isNewsSource = isset($ev['source']) && ($ev['source'] === 'MaxPreps' || $ev['source'] === 'Yahoo Sports');
                
                if ($isNewsSource) {
                    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
                    $slug = trim($slug, '-');
                    $link = SITE_PATH . "news/" . $slug . "-" . $id;
                } else {
                    $link = SITE_PATH . "player.php?event={$id}";
                }
                
                $label = $isNewsSource ? 'News' : 'Replay';
                $color = $isNewsSource ? '#00c' : '#c00';
            ?>
            <article class="news-list-item">
                <a href="<?php echo $link; ?>" class="news-list-img-wrapper">
                    <img src="<?php echo $img; ?>" class="news-list-img" alt="<?php echo htmlspecialchars($title); ?>" loading="lazy" onerror="this.src='<?php echo $defaultImg; ?>'">
                    <span class="news-type-badge <?php echo strtolower($label); ?>"><?php echo $label; ?></span>
                </a>
                <div class="news-list-content">
                    <h3 class="news-list-title"><a href="<?php echo $link; ?>"><?php echo htmlspecialchars($title); ?></a></h3>
                    <div class="news-meta">
                        <span><?php echo $time; ?></span>
                        <span>•</span>
                        <span><?php echo htmlspecialchars($sport); ?></span>
                    </div>
                    <p class="news-excerpt"><?php echo htmlspecialchars(substr(strip_tags($desc), 0, 120)) . '...'; ?></p>
                </div>
            </article>

            <!-- In-Feed Advertisement -->
            <?php if ($loopCount === 3 && !empty($config['ads']['feed_middle'])): ?>
                <div class="ad-container" style="margin: 20px 0; text-align: center;">
                    <?php echo $config['ads']['feed_middle']; ?>
                </div>
            <?php endif; ?>

            <?php endforeach; ?>
        </div>
        
        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination-controls" style="display:flex; justify-content:center; gap:10px; margin-top:30px;">
            <?php if ($currentPage > 1): ?>
                <a href="?page=<?php echo ($currentPage - 1); ?>#news" class="btn btn-secondary">&laquo; Previous</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == 1 || $i == $totalPages || ($i >= $currentPage - 1 && $i <= $currentPage + 1)): ?>
                    <?php if ($i == $currentPage): ?>
                        <span class="btn btn-primary" style="background-color: #004b8d; color: white;"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>#news" class="btn btn-secondary"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php elseif ($i == 2 || $i == $totalPages - 1): ?>
                    <span class="pagination-dots">...</span>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?php echo ($currentPage + 1); ?>#news" class="btn btn-secondary">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
    </section>
    <?php endif; ?>

    <!-- Upcoming Events Grid -->
    <section class="news-section" id="upcoming">
        <div class="section-header">
            <h2 class="section-title">Upcoming Broadcasts</h2>
        </div>
        
        <div class="news-grid">
            <?php 
            $upcomingLoopCount = 0;
            foreach($upcomingEvents as $ev): 
                $upcomingLoopCount++;
                $img = resolve_image_url($ev['background_image'] ?? $ev['hero_image'] ?? $ev['thumbnail'] ?? '');
                $title = $ev['headline'] ?? $ev['title'] ?? 'Event';
                
                // Extract Team Names for Title
                $homeTeamName = $ev['homeTeam']['name'] ?? $ev['metadata']['home_team_name'] ?? $ev['metadata']['home_team'] ?? $ev['second_title'] ?? $ev['home'] ?? '';
                $awayTeamName = $ev['awayTeam']['name'] ?? $ev['metadata']['away_team_name'] ?? $ev['metadata']['away_team'] ?? $ev['first_title'] ?? $ev['away'] ?? '';
                
                // Fallback to participants names
                if ((empty($homeTeamName) || empty($awayTeamName)) && !empty($ev['participants'])) {
                    foreach ($ev['participants'] as $p) {
                         if (isset($p['is_home']) && $p['is_home'] && !empty($p['name'])) $homeTeamName = $p['name'];
                         if (isset($p['is_home']) && !$p['is_home'] && !empty($p['name'])) $awayTeamName = $p['name'];
                    }
                }

                // Fallback: Parse from title if available and we are missing teams
                if ((empty($homeTeamName) || empty($awayTeamName)) && !empty($title) && stripos($title, ' vs ') !== false) {
                    $parts = preg_split('/\s+vs\s+/i', $title);
                    if (count($parts) >= 2) {
                        $homeTeamName = $homeTeamName ?: trim($parts[0]);
                        $awayTeamName = $awayTeamName ?: trim($parts[1]);
                    }
                }

                // ✅ Filter out Invalid or TBD Teams
                // User Request: "yang team TBD tidak perlu di munculkan, validasi cukup munculkan data yang lengkap memiliki home dan away team"
                if (empty($homeTeamName) || empty($awayTeamName) || 
                    stripos($homeTeamName, 'TBD') !== false || stripos($awayTeamName, 'TBD') !== false) {
                    continue;
                }

                if (!empty($homeTeamName) && !empty($awayTeamName)) {
                    $title = $homeTeamName . ' vs ' . $awayTeamName;
                }
                
                // Truncate title if too long
                if (strlen($title) > 60) {
                    $title = substr($title, 0, 57) . '...';
                }

                $id = $ev['key'] ?? '';
                $sport = $ev['activity_or_sport'] ?? 'HS Sports';
                $time = get_friendly_date($ev['date'] ?? $ev['startTime'] ?? null);
                
                // Extract logos
                $homeLogo = $ev['second_logo'] ?? $ev['homeTeam']['logo'] ?? $ev['metadata']['home_logo'] ?? null;
                $awayLogo = $ev['first_logo'] ?? $ev['awayTeam']['logo'] ?? $ev['metadata']['away_logo'] ?? null;
                
                // Fallback to participants
                if (empty($homeLogo) && !empty($ev['participants'])) {
                    foreach ($ev['participants'] as $p) {
                         if (isset($p['is_home']) && $p['is_home'] && !empty($p['logo_url'])) $homeLogo = $p['logo_url'];
                         if (isset($p['is_home']) && !$p['is_home'] && !empty($p['logo_url'])) $awayLogo = $p['logo_url'];
                    }
                }
            ?>
            <article class="news-card">
                <a href="player.php?event=<?php echo $id; ?>">
                    <img src="<?php echo $img; ?>" class="news-card-img" alt="" loading="lazy" onerror="this.src='<?php echo $defaultImg; ?>'">
                    
                    <?php if ($homeLogo || $awayLogo): ?>
                    <div class="broadcast-logos">
                        <?php if ($homeLogo): ?>
                        <div class="bc-logo"><img src="<?php echo resolve_image_url($homeLogo); ?>" alt="Home" onerror="this.style.display='none'"></div>
                        <?php endif; ?>
                        
                        <span class="bc-vs">VS</span>
                        
                        <?php if ($awayLogo): ?>
                        <div class="bc-logo"><img src="<?php echo resolve_image_url($awayLogo); ?>" alt="Away" onerror="this.style.display='none'"></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </a>
                <span class="category-tag" style="font-size:0.7rem;"><?php echo htmlspecialchars($sport); ?></span>
                <h3 class="news-card-title">
                    <a href="player.php?event=<?php echo $id; ?>"><?php echo htmlspecialchars($title); ?></a>
                </h3>
                <div class="news-card-meta">
                    <span>📅 <?php echo $time; ?></span>
                </div>
            </article>

            <!-- In-Grid Advertisement -->
            <?php if ($upcomingLoopCount === 6 && !empty($config['ads']['upcoming_middle'])): ?>
                <div class="ad-container" style="grid-column: 1 / -1; margin: 20px 0; text-align: center;">
                    <?php echo $config['ads']['upcoming_middle']; ?>
                </div>
            <?php endif; ?>

            <?php 
                // Limit strictly to 12 items displayed
                if ($upcomingLoopCount >= 12) break;
            endforeach; 
            ?>
        </div>
    </section>

</main>

    <?php include __DIR__ . '/footer.php'; ?>

    <script>
        (function() {
            // Share configuration
            const pageUrl = "<?php echo $pageUrl; ?>";
            const shareTemplateWithUrl = "<?php echo addslashes($ogDesc); ?> " + pageUrl;
            const shareTemplateWithoutUrl = "<?php echo addslashes($ogDesc); ?>";

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
                        // Redirect to Facebook share URL
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
                            // Fallback
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
