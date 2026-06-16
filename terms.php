<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
$config = require __DIR__ . '/config.php';

$pageTitle = 'Terms of Use';
$metaDescription = 'Terms of Use for MaxPreps News - Read our terms and conditions for using our website.';
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
    <link rel="canonical" href="<?php echo base_origin() . SITE_PATH; ?>terms.php">
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
            background: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
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
        <h1>Terms of Use</h1>
        <p class="legal-updated">Last Updated: <?php echo date('F j, Y'); ?></p>
    </div>

    <div class="legal-section">
        <p>Welcome to MaxPreps News. By accessing or using our website, you agree to be bound by these Terms of Use. If you do not agree with any part of these terms, you may not use our website.</p>
    </div>

    <div class="legal-section">
        <h2>1. Acceptance of Terms</h2>
        <p>By accessing and using this website, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>
    </div>

    <div class="legal-section">
        <h2>2. Use License</h2>
        <p>Permission is granted to temporarily access the materials (information or software) on MaxPreps News's website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
        <ul>
            <li>Modify or copy the materials</li>
            <li>Use the materials for any commercial purpose or for any public display (commercial or non-commercial)</li>
            <li>Attempt to decompile or reverse engineer any software contained on the website</li>
            <li>Remove any copyright or other proprietary notations from the materials</li>
            <li>Transfer the materials to another person or "mirror" the materials on any other server</li>
        </ul>
    </div>

    <div class="legal-section">
        <h2>3. Content Disclaimer</h2>
        <p>The materials on MaxPreps News's website are provided on an 'as is' basis. MaxPreps News makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>
        <p>All content on this website is for informational purposes only. We aggregate and rewrite publicly available sports news and information from various sources. While we strive for accuracy, we make no guarantees about the completeness, reliability, or accuracy of this information.</p>
    </div>

    <div class="legal-section">
        <h2>4. Content Ownership and Copyright</h2>
        <h3>4.1 Our Content</h3>
        <p>All rewritten articles, original commentary, and unique content created by MaxPreps News is protected by copyright law. You may not reproduce, distribute, or create derivative works from our content without express written permission.</p>
        
        <h3>4.2 Third-Party Content</h3>
        <p>We aggregate and transform publicly available sports news and data. Original sources retain copyright to their original content. Our rewritten versions constitute transformative fair use under copyright law.</p>
        
        <h3>4.3 Embedded Media</h3>
        <p>Videos, images, and other media embedded on our site may be copyright of their respective owners. We display such content under fair use principles or with appropriate licensing.</p>
    </div>

    <div class="legal-section">
        <h2>5. User Conduct</h2>
        <p>You agree not to:</p>
        <ul>
            <li>Use the website for any unlawful purpose or in violation of any applicable law</li>
            <li>Attempt to gain unauthorized access to any portion of the website</li>
            <li>Use any automated system (including "robots," "spiders," or "offline readers") to access the website</li>
            <li>Interfere with or disrupt the website or servers or networks connected to the website</li>
            <li>Transmit any viruses, worms, or any code of a destructive nature</li>
        </ul>
    </div>

    <div class="legal-highlight">
        <h2>6. Limitation of Liability</h2>
        <p><strong>Important:</strong> In no event shall MaxPreps News or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on MaxPreps News's website, even if MaxPreps News or a MaxPreps News authorized representative has been notified orally or in writing of the possibility of such damage.</p>
    </div>

    <div class="legal-section">
        <h2>7. Links to Third-Party Websites</h2>
        <p>Our website may contain links to third-party websites or services that are not owned or controlled by MaxPreps News. We have no control over, and assume no responsibility for, the content, privacy policies, or practices of any third-party websites or services.</p>
    </div>

    <div class="legal-section">
        <h2>8. Advertising</h2>
        <p>We display advertisements through Google AdSense and other advertising partners. These advertisements may use cookies and other tracking technologies. Please review our <a href="/privacy.php">Privacy Policy</a> for more information about how advertising works on our site.</p>
    </div>

    <div class="legal-section">
        <h2>9. Indemnification</h2>
        <p>You agree to indemnify and hold harmless MaxPreps News and its affiliates, officers, agents, and employees from any claim or demand, including reasonable attorneys' fees, made by any third party due to or arising out of your use of the website, your violation of these Terms of Use, or your violation of any rights of another.</p>
    </div>

    <div class="legal-section">
        <h2>10. Modifications to Service</h2>
        <p>MaxPreps News reserves the right to modify or discontinue, temporarily or permanently, the service (or any part thereof) with or without notice at any time. You agree that MaxPreps News shall not be liable to you or to any third party for any modification, suspension, or discontinuance of the service.</p>
    </div>

    <div class="legal-section">
        <h2>11. Governing Law</h2>
        <p>These terms and conditions are governed by and construed in accordance with the laws of the United States, and you irrevocably submit to the exclusive jurisdiction of the courts in that location.</p>
    </div>

    <div class="legal-section">
        <h2>12. Changes to Terms</h2>
        <p>We reserve the right to revise these terms of use at any time without notice. By using this website, you are agreeing to be bound by the then-current version of these Terms of Use.</p>
    </div>

    <div class="legal-contact">
        <h2>13. Contact Information</h2>
        <p>If you have any questions about these Terms of Use, please contact us:</p>
        <p>
            <strong>Email:</strong> <?php echo htmlspecialchars($config['support_email'] ?? 'legal@maxpreps.news'); ?><br>
            <strong>Website:</strong> <a href="<?php echo base_origin(); ?>"><?php echo parse_url(base_origin(), PHP_URL_HOST); ?></a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>
