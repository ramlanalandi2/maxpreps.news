<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
$config = require __DIR__ . '/config.php';

$pageTitle = 'Privacy Policy';
$metaDescription = 'Privacy Policy for MaxPreps News - Learn how we collect, use, and protect your personal information.';
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
    <link rel="canonical" href="<?php echo base_origin() . SITE_PATH; ?>privacy.php">
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/common.css">
    <style>
        .legal-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        .legal-header {
            margin-bottom: 30px;
            border-bottom: 3px solid #0066cc;
            padding-bottom: 20px;
        }
        .legal-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #1a1a1a;
        }
        .legal-updated {
            color: #666;
            font-size: 0.9rem;
        }
        .legal-section {
            margin-bottom: 35px;
        }
        .legal-section h2 {
            font-size: 1.5rem;
            color: #0066cc;
            margin-bottom: 15px;
            margin-top: 30px;
        }
        .legal-section h3 {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 10px;
            margin-top: 20px;
        }
        .legal-section p, .legal-section li {
            line-height: 1.8;
            color: #444;
            margin-bottom: 15px;
        }
        .legal-section ul {
            margin-left: 20px;
        }
        .legal-section ul li {
            margin-bottom: 10px;
        }
        .legal-highlight {
            background: #f0f8ff;
            padding: 15px;
            border-left: 4px solid #0066cc;
            margin: 20px 0;
        }
        .legal-contact {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 40px;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/templates/header.php'; ?>

<div class="legal-container">
    <div class="legal-header">
        <h1>Privacy Policy</h1>
        <p class="legal-updated">Last Updated: <?php echo date('F j, Y'); ?></p>
    </div>

    <div class="legal-section">
        <p>Welcome to MaxPreps News ("we," "our," or "us"). We are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website.</p>
    </div>

    <div class="legal-section">
        <h2>1. Information We Collect</h2>
        
        <h3>1.1 Automatically Collected Information</h3>
        <p>When you visit our website, we automatically collect certain information about your device, including:</p>
        <ul>
            <li><strong>Device Information:</strong> Browser type, IP address, operating system, device identifiers</li>
            <li><strong>Usage Data:</strong> Pages visited, time spent on pages, links clicked, referral URLs</li>
            <li><strong>Cookies and Similar Technologies:</strong> We use cookies and similar tracking technologies to enhance your browsing experience</li>
        </ul>

        <h3>1.2 Information You Provide</h3>
        <p>We may collect information that you voluntarily provide to us, such as:</p>
        <ul>
            <li>Contact information (name, email address) when you contact us</li>
            <li>Comments or feedback you submit</li>
            <li>Newsletter subscription information</li>
        </ul>
    </div>

    <div class="legal-section">
        <h2>2. How We Use Your Information</h2>
        <p>We use the collected information for the following purposes:</p>
        <ul>
            <li><strong>Website Operation:</strong> To provide, maintain, and improve our website functionality</li>
            <li><strong>Content Personalization:</strong> To understand user preferences and enhance user experience</li>
            <li><strong>Analytics:</strong> To analyze usage patterns and improve our content and services</li>
            <li><strong>Advertising:</strong> To display relevant advertisements through Google AdSense and other advertising partners</li>
            <li><strong>Communication:</strong> To respond to your inquiries and send important updates</li>
            <li><strong>Legal Compliance:</strong> To comply with applicable laws and regulations</li>
        </ul>
    </div>

    <div class="legal-highlight">
        <h2>3. Google AdSense and Third-Party Advertising</h2>
        <p><strong>Important Information About Advertising:</strong></p>
        <p>We use Google AdSense to display advertisements on our website. Google AdSense may use cookies and web beacons to collect information about your visits to this and other websites to provide advertisements about goods and services of interest to you.</p>
        <p>Third-party vendors, including Google, use cookies to serve ads based on your prior visits to our website or other websites. Google's use of advertising cookies enables it and its partners to serve ads based on your visit to our site and/or other sites on the Internet.</p>
        <p>You may opt out of personalized advertising by visiting <a href="https://www.google.com/settings/ads" target="_blank" rel="noopener">Google Ads Settings</a> or <a href="http://www.aboutads.info" target="_blank" rel="noopener">www.aboutads.info</a>.</p>
    </div>

    <div class="legal-section">
        <h2>4. Cookies and Tracking Technologies</h2>
        <p>We use cookies and similar tracking technologies including:</p>
        <ul>
            <li><strong>Essential Cookies:</strong> Required for website functionality</li>
            <li><strong>Analytics Cookies:</strong> Help us understand how visitors interact with our website</li>
            <li><strong>Advertising Cookies:</strong> Used to deliver relevant advertisements</li>
        </ul>
        <p>You can control cookies through your browser settings. However, disabling cookies may affect website functionality.</p>
    </div>

    <div class="legal-section">
        <h2>5. Information Sharing and Disclosure</h2>
        <p>We do not sell your personal information. We may share your information with:</p>
        <ul>
            <li><strong>Service Providers:</strong> Third-party companies that help us operate our website (hosting, analytics, advertising)</li>
            <li><strong>Advertising Partners:</strong> Google AdSense and other ad networks</li>
            <li><strong>Legal Requirements:</strong> When required by law or to protect our rights</li>
        </ul>
    </div>

    <div class="legal-section">
        <h2>6. Data Security</h2>
        <p>We implement reasonable security measures to protect your information from unauthorized access, alteration, disclosure, or destruction. However, no internet transmission is completely secure, and we cannot guarantee absolute security.</p>
    </div>

    <div class="legal-section">
        <h2>7. Your Privacy Rights</h2>
        <p>Depending on your location, you may have the following rights:</p>
        <ul>
            <li><strong>Access:</strong> Request access to your personal information</li>
            <li><strong>Correction:</strong> Request correction of inaccurate information</li>
            <li><strong>Deletion:</strong> Request deletion of your personal information</li>
            <li><strong>Opt-Out:</strong> Opt-out of marketing communications and personalized advertising</li>
        </ul>
        <p>To exercise these rights, please contact us using the information below.</p>
    </div>

    <div class="legal-section">
        <h2>8. Children's Privacy</h2>
        <p>Our website is not intended for children under 13 years of age. We do not knowingly collect personal information from children. If you believe we have collected information from a child, please contact us immediately.</p>
    </div>

    <div class="legal-section">
        <h2>9. International Users</h2>
        <p>Our website is operated in the United States. If you are accessing our website from outside the United States, please be aware that your information may be transferred to, stored, and processed in the United States.</p>
    </div>

    <div class="legal-section">
        <h2>10. Changes to This Privacy Policy</h2>
        <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last Updated" date.</p>
    </div>

    <div class="legal-contact">
        <h2>11. Contact Us</h2>
        <p>If you have any questions about this Privacy Policy or our privacy practices, please contact us:</p>
        <p>
            <strong>Email:</strong> <?php echo htmlspecialchars($config['support_email'] ?? 'privacy@maxpreps.news'); ?><br>
            <strong>Website:</strong> <a href="<?php echo base_origin(); ?>"><?php echo parse_url(base_origin(), PHP_URL_HOST); ?></a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>
