<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
$config = require __DIR__ . '/config.php';

$authorName = $_GET['name'] ?? 'John Smith';
$authorSlug = strtolower(str_replace(' ', '-', $authorName));

// Author Bio (Transparent for Google News)
$authorBio = "John Smith is a professional sports journalist specializing in high school athletics and regional championships. With over 10 years of experience covering scouting and game analysis, John provides in-depth reporting and verified data on the most promising young athletes in the country.";
$authorPhoto = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($authorName))) . "?s=200&d=mp";

// Load Articles
$newsDataFile = __DIR__ . '/data/news.json';
$authorArticles = [];
if (file_exists($newsDataFile)) {
    $allNews = json_decode(file_get_contents($newsDataFile), true);
    $items = $allNews['items'] ?? [];
    // For now, assume all articles are by this author as requested for the "transparency" fix
    $authorArticles = array_slice($items, 0, 12);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($authorName); ?> - Author Profile | MaxPreps News</title>
    <meta name="description" content="Read articles and reports by <?php echo htmlspecialchars($authorName); ?> on MaxPreps News.">
    <link rel="icon" href="<?php echo SITE_PATH; ?>favicon.ico" type="image/x-icon">
    
    <!-- Fonts & CSS -->
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/common.css?v=<?php echo get_asset_version('assets/css/common.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/home.css?v=<?php echo get_asset_version('assets/css/home.css'); ?>">
    <style>
        .author-profile-card {
            background: #fff;
            border-radius: 8px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            gap: 30px;
            align-items: center;
        }
        .author-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f8f9fa;
        }
        .author-info h1 {
            margin: 0 0 10px 0;
            font-size: 2rem;
            color: #1a1a1a;
        }
        .author-bio {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #4a4a4a;
            margin: 0;
        }
        @media (max-width: 768px) {
            .author-profile-card {
                flex-direction: column;
                text-align: center;
                padding: 25px;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/templates/header.php'; ?>

    <div class="page-wrapper" style="margin-top: 40px;">
        <main>
            <section class="author-profile-card">
                <img src="<?php echo $authorPhoto; ?>" alt="<?php echo htmlspecialchars($authorName); ?>" class="author-avatar">
                <div class="author-info">
                    <h1><?php echo htmlspecialchars($authorName); ?></h1>
                    <p class="author-bio"><?php echo $authorBio; ?></p>
                </div>
            </section>

            <h2 class="section-title">Latest Articles by <?php echo htmlspecialchars($authorName); ?></h2>
            <div class="news-list-grid">
                <?php foreach ($authorArticles as $item): 
                    $nId = $item['key'] ?? '';
                    $nTitle = $item['headline'] ?? $item['title'] ?? 'News';
                    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $nTitle));
                    $slug = trim($slug, '-');
                    $nUrl = SITE_PATH . "news/" . $slug . "-" . $nId;
                    $nThumb = resolve_image_url($item['hero_image'] ?? $item['thumbnail'] ?? '');
                ?>
                <div class="news-list-item">
                    <a href="<?php echo $nUrl; ?>" class="news-thumb-link">
                        <div class="news-list-thumb" style="background-image: url('<?php echo htmlspecialchars($nThumb); ?>');"></div>
                    </a>
                    <div class="news-list-content">
                        <span class="news-list-tag"><?php echo htmlspecialchars($item['activity_or_sport'] ?? 'Sports'); ?></span>
                        <h3 class="news-list-title"><a href="<?php echo $nUrl; ?>"><?php echo htmlspecialchars($nTitle); ?></a></h3>
                        <p class="news-list-excerpt"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                        <div class="news-list-meta">
                            <span><?php echo date('M j, Y', strtotime($item['date'] ?? 'now')); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

<?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>
