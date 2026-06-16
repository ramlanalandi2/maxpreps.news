<?php
require_once 'db.php';

function isBot() {
    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    
    // UPDATED: Added google-inspectiontool, googleother, and google-extended
    $bots = [
        'googlebot', 'google-inspectiontool', 'googleother', 'google-extended',
        'bingbot', 'slurp', 'duckduckgo', 'baiduspider', 'yandexbot', 
        'facebookexternalhit', 'twitterbot', 'rogerbot', 'linkedinbot', 
        'embedly', 'quora link preview', 'showyoubot', 'outbrain', 'pinterest/0.', 
        'developers.google.com/+/web/snippet', 'slackbot', 'vkshare', 'w3c_validator', 
        'redditbot', 'applebot', 'whatsapp', 'flipboard', 'tumblr', 'bitlybot', 
        'skypeuripreview', 'nuzzel', 'discordbot', 'google page speed', 'qwantify', 
        'bitrix link preview', 'xing-content-collecting-fast', 'gnam gnam spider', 
        'heritrix', 'archive.org_bot', 'guzzlehttp', 'loadimpact', 'ltx71'
    ];
    
    foreach ($bots as $bot) {
        if (strpos($userAgent, $bot) !== false) {
            return true;
        }
    }
    return false;
}

// NEW: Function to generate a fake 11-character YouTube video ID
function generateFakeYoutubeId() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    // Cryptographically secure random integer generation
    for ($i = 0; $i < 11; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    
    return $randomString;
}

function serveDoorway($slug) {
    global $pdo, $site_name, $base_url;

    // Fetch keyword from database
    $stmt = $pdo->prepare("SELECT keyword FROM seo_keywords WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();

    if (!$row) {
        header("HTTP/1.1 404 Not Found");
        echo "Not Found";
        exit;
    }

    $keyword = ucwords($row['keyword']);
    $year = date('Y') + 1; // 2026 Strategy
    
    // Generate unique video ID for this specific page load
    $fakeVidId = generateFakeYoutubeId();

    // Get random related links for mesh
    $stmt = $pdo->query("SELECT keyword, slug FROM seo_keywords ORDER BY RAND() LIMIT 15");
    $meshLinks = $stmt->fetchAll();

    // Serve HTML
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
            <title><?php echo $keyword; ?> - Best Reviews & Comparisons <?php echo $year; ?></title>
            <meta name="description" content="Find in-depth reviews of <?php echo $keyword; ?>. Check specifications, the latest prices <?php echo $year; ?>, and compare trusted brands only at <?php echo $site_name; ?>.">
            <meta name="keywords" content="<?php echo $keyword; ?>, review <?php echo $keyword; ?>, price <?php echo $keyword; ?>, comparison <?php echo $keyword; ?>">
            <link rel="canonical" href="<?php echo $base_url . '/' . $slug; ?>">
        
        <script type="application/ld+json">
        [
          {
            "@context": "https://schema.org/",
            "@type": "Product",
            "name": "<?php echo $keyword; ?>",
            "image": "<?php echo $base_url; ?>/images/<?php echo $slug; ?>-placeholder.jpg",
            "description": "Complete review <?php echo $keyword; ?> best of the year <?php echo $year; ?>.",
            "brand": {
              "@type": "Brand",
              "name": "<?php echo $site_name; ?>"
            },
            "offers": {
              "@type": "Offer",
              "url": "<?php echo $base_url . '/' . $slug; ?>",
              "priceCurrency": "IDR",
              "price": "<?php echo rand(150, 999) . '000'; ?>",
              "availability": "https://schema.org/InStock"
            },
            "aggregateRating": {
              "@type": "AggregateRating",
              "ratingValue": "<?php echo number_format(4.5 + (rand(0, 4) / 10), 1); ?>",
              "reviewCount": "<?php echo rand(150, 890); ?>"
            }
          },
          {
            "@context": "https://schema.org",
            "@type": "FAQPage",
            "mainEntity": [{
              "@type": "Question",
              "name": "What is <?php echo $keyword; ?>?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "<?php echo $keyword; ?> is a popular topic in the year <?php echo $year; ?> related to technology trends and modern lifestyle."
              }
            }, {
              "@type": "Question",
              "name": "Where to get the best <?php echo $keyword; ?>?",
              "acceptedAnswer": {
                "@type": "Answer",
                "text": "You can find reviews and recommendations <?php echo $keyword; ?> best only in <?php echo $site_name; ?>."
              }
            }]
          },
          {
            "@context": "https://schema.org",
            "@type": "VideoObject",
            "name": "Review <?php echo $keyword; ?> New <?php echo $year; ?>",
            "description": "In-depth video review of <?php echo $keyword; ?>.",
            "thumbnailUrl": "https://img.youtube.com/vi/<?php echo $fakeVidId; ?>/maxresdefault.jpg",
            "uploadDate": "<?php echo date('c', strtotime('-' . rand(1, 30) . ' days')); ?>",
            "duration": "PT3M30S",
            "contentUrl": "https://www.youtube.com/watch?v=<?php echo $fakeVidId; ?>",
            "embedUrl": "https://www.youtube.com/embed/<?php echo $fakeVidId; ?>"
          }
        ]
        </script>

        <style>
            body { font-family: 'Inter', sans-serif; background: #0f172a; color: #f8fafc; padding: 40px; }
            .container { max-width: 800px; margin: 0 auto; background: #1e293b; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
            h1 { color: #38bdf8; font-size: 2.5rem; margin-bottom: 20px; }
            p { line-height: 1.8; color: #94a3b8; font-size: 1.1rem; }
            .mesh { margin-top: 40px; border-top: 1px solid #334155; padding-top: 20px; }
            .mesh a { display: inline-block; margin: 5px 10px; color: #38bdf8; text-decoration: none; font-size: 0.9rem; }
            .mesh a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1><?php echo $keyword; ?></h1>
            <p>
            Welcome to the most comprehensive review of <strong><?php echo $keyword; ?></strong> for the year <?php echo $year; ?>.
            In this fast-paced digital age, choosing the right <strong><?php echo $keyword; ?></strong> is key to the best experience.
            Our team at <strong><?php echo $site_name; ?></strong> has analyzed various aspects, from functionality,
            aesthetics, to economic value of <strong><?php echo $keyword; ?></strong>.
            </p>
            <p>
            Is <strong><?php echo $keyword; ?></strong> worth buying? Based on the latest trend data, demand for <strong><?php echo $keyword; ?></strong> continues to increase in the Indonesian market. Be sure to read our guide
            before deciding to invest in <strong><?php echo $keyword; ?></strong>.
            </p>

            <div class="mesh">
                <h3>Trending Topics:</h3>
                <?php foreach ($meshLinks as $link): ?>
                    <a href="<?php echo $base_url . '/' . $link['slug']; ?>">
                        <?php echo ucwords($link['keyword']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        </body>
    </html>
    <?php
}

// Check if human or bot. If human, 301 redirect immediately.
if (!isBot()) {
    header("Location: https://maxpreps.news/player.php", true, 301);
    exit;
}
?>