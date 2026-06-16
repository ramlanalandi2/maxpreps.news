<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
$config = require __DIR__ . '/config.php';

$pageTitle = 'About Us';
$metaDescription = 'Learn about MaxPreps News - your premier source for high school sports news, live games, and highlights powered by the NFHS Network.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | MaxPreps News</title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="robots" content="index, follow">
    <link rel="icon" href="<?php echo SITE_PATH; ?>favicon.ico" type="image/x-icon">
    <link rel="canonical" href="<?php echo base_origin() . SITE_PATH; ?>about.php">
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/common.css">
    <style>
        .about-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .about-header {
            text-align: center;
            margin-bottom: 50px;
        }
        .about-header h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #1a1a1a;
        }
        .about-header p {
            font-size: 1.2rem;
            color: #666;
        }
        .about-section {
            margin-bottom: 40px;
        }
        .about-section h2 {
            font-size: 1.8rem;
            color: #0066cc;
            margin-bottom: 15px;
        }
        .about-section p {
            line-height: 1.8;
            color: #444;
            margin-bottom: 15px;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }
        .feature-card {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .feature-card h3 {
            color: #0066cc;
            margin-bottom: 10px;
        }
        .feature-card p {
            color: #666;
        }
        .cta-section {
            background: linear-gradient(135deg, #0066cc, #004999);
            color: white;
            padding: 50px 30px;
            border-radius: 12px;
            text-align: center;
            margin: 50px 0;
        }
        .cta-section h2 {
            color: white;
            margin-bottom: 20px;
        }
        .cta-button {
            display: inline-block;
            background: white;
            color: #0066cc;
            padding: 15px 40px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/templates/header.php'; ?>

<div class="about-container">
    <div class="about-header">
        <h1>About MaxPreps News</h1>
        <p>Your Premier Source for High School Sports</p>
    </div>

    <div class="about-section">
        <h2>Who We Are</h2>
        <p>MaxPreps News is a dedicated platform bringing you the latest high school sports news, live game coverage, and on-demand highlights. We're passionate about celebrating the achievements of student-athletes across the nation.</p>
        <p>Powered by the NFHS Network and PlayOn! Sports, we provide comprehensive coverage of high school athletics, from basketball and football to soccer, volleyball, and more.</p>
    </div>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">🏀</div>
            <h3>Live Games</h3>
            <p>Watch live high school sports events from across the country in real-time.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">📰</div>
            <h3>Latest News</h3>
            <p>Stay updated with breaking news, game recaps, and player spotlights.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🎥</div>
            <h3>On-Demand Highlights</h3>
            <p>Catch up on games you missed with our extensive on-demand library.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🏆</div>
            <h3>Championship Coverage</h3>
            <p>Follow teams through playoffs and championships in every sport.</p>
        </div>
    </div>

    <div class="about-section">
        <h2>Our Mission</h2>
        <p>We believe that every student-athlete deserves recognition for their hard work and dedication. Our mission is to:</p>
        <ul style="line-height: 2; color: #444; margin-left: 20px;">
            <li>Provide accessible, high-quality coverage of high school sports</li>
            <li>Celebrate student-athlete achievements and milestones</li>
            <li>Connect communities through the power of sports</li>
            <li>Support families, coaches, and fans with reliable sports information</li>
        </ul>
    </div>

    <div class="about-section">
        <h2>Content & Coverage</h2>
        <p><strong>What We Offer:</strong></p>
        <ul style="line-height: 2; color: #444; margin-left: 20px;">
            <li><strong>Live Streaming:</strong> Watch games as they happen with our live streaming platform</li>
            <li><strong>Game Recaps:</strong> Detailed summaries and highlights of recent games</li>
            <li><strong>Player Profiles:</strong> Spotlight features on standout athletes</li>
            <li><strong>Team Rankings:</strong> Follow your favorite teams' performance</li>
            <li><strong>Championship Coverage:</strong> Complete playoff and championship game coverage</li>
        </ul>
    </div>

    <div class="cta-section">
        <h2>Start Watching Today</h2>
        <p>Discover live games, catch up on highlights, and stay connected with high school sports.</p>
        <a href="<?php echo SITE_PATH; ?>" class="cta-button">Explore Now</a>
    </div>

    <div class="about-section">
        <h2>Powered By</h2>
        <p>MaxPreps News is powered by the <strong>NFHS Network</strong>, the premier platform for high school sports streaming, operated by <strong>PlayOn! Sports</strong>. Together, we're bringing high school athletics to fans everywhere.</p>
    </div>

    <div class="about-section">
        <h2>Contact Us</h2>
        <p>Have questions, feedback, or need support? We'd love to hear from you!</p>
        <p><a href="<?php echo SITE_PATH; ?>contact.php" style="color: #0066cc; text-decoration: none; font-weight: 600;">→ Contact Support</a></p>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>
