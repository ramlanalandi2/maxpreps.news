<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
$config = require __DIR__ . '/config.php';

$pageTitle = 'DMCA Notice';
$metaDescription = 'DMCA Copyright Infringement Notice and Takedown Policy for MaxPreps News.';
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
    <link rel="canonical" href="<?php echo base_origin() . SITE_PATH; ?>dmca.php">
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
            border-bottom: 3px solid #dc3545;
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
            color: #dc3545;
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
        .legal-section ul, .legal-section ol {
            margin-left: 20px;
        }
        .legal-section ul li, .legal-section ol li {
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
        .dmca-form {
            background: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/templates/header.php'; ?>

<div class="legal-container">
    <div class="legal-header">
        <h1>DMCA Notice & Takedown Policy</h1>
        <p class="legal-updated">Last Updated: <?php echo date('F j, Y'); ?></p>
    </div>

    <div class="legal-section">
        <p>MaxPreps News respects the intellectual property rights of others and expects its users to do the same. In accordance with the Digital Millennium Copyright Act of 1998 (DMCA), we will respond expeditiously to claims of copyright infringement committed using our website.</p>
    </div>

    <div class="legal-section">
        <h2>1. Reporting Copyright Infringement</h2>
        <p>If you believe that content available on our website infringes your copyright, please notify us by providing our copyright agent with the following information:</p>
        
        <ol>
            <li><strong>Identification of the copyrighted work</strong> claimed to have been infringed, or, if multiple copyrighted works are covered by a single notification, a representative list of such works.</li>
            <li><strong>Identification of the material</strong> that is claimed to be infringing or to be the subject of infringing activity and that is to be removed or access to which is to be disabled, and information reasonably sufficient to permit us to locate the material (including URL).</li>
            <li><strong>Your contact information</strong>, including your address, telephone number, and email address.</li>
            <li><strong>A statement</strong> that you have a good faith belief that use of the material in the manner complained of is not authorized by the copyright owner, its agent, or the law.</li>
            <li><strong>A statement</strong> that the information in the notification is accurate, and under penalty of perjury, that you are authorized to act on behalf of the owner of an exclusive right that is allegedly infringed.</li>
            <li><strong>Your physical or electronic signature</strong> (typing your full name will suffice).</li>
        </ol>
    </div>

    <div class="dmca-form">
        <h3>Required Information Template</h3>
        <p>Please include the following in your DMCA notice:</p>
        <pre style="background: white; padding: 15px; border-radius: 4px; overflow-x: auto;">
Subject: DMCA Takedown Request

1. Copyright Owner: [Your name or company name]
2. Copyrighted Work: [Description of your work]
3. Infringing Material URL: [Specific URL on our site]
4. Contact Information:
   - Name: [Your full name]
   - Address: [Your physical address]
   - Phone: [Your phone number]
   - Email: [Your email address]
5. Good Faith Statement: I have a good faith belief that use of the 
   copyrighted materials described above is not authorized by the 
   copyright owner, its agent, or the law.
6. Accuracy Statement: I swear, under penalty of perjury, that the 
   information in this notification is accurate and that I am the 
   copyright owner or authorized to act on behalf of the owner.
7. Signature: [Your full legal name]
8. Date: [Current date]
        </pre>
    </div>

    <div class="legal-section">
        <h2>2. Counter-Notification</h2>
        <p>If you believe that your content that was removed (or to which access was disabled) is not infringing, or that you have the authorization from the copyright owner to post and use the content, you may send a counter-notice containing the following information to our copyright agent:</p>
        
        <ol>
            <li>Your physical or electronic signature</li>
            <li>Identification of the content that has been removed or to which access has been disabled and the location at which the content appeared before it was removed or disabled</li>
            <li>A statement that you have a good faith belief that the content was removed or disabled as a result of mistake or a misidentification of the content</li>
            <li>Your name, address, telephone number, and email address</li>
            <li>A statement that you consent to the jurisdiction of the federal court in your district and that you will accept service of process from the person who provided notification of the alleged infringement</li>
        </ol>
    </div>

    <div class="legal-highlight">
        <h2>3. Repeat Infringer Policy</h2>
        <p><strong>Important:</strong> In accordance with the DMCA and other applicable law, we have adopted a policy of terminating, in appropriate circumstances, users who are deemed to be repeat infringers. We may also limit access to the website and/or terminate accounts of any users who infringe any intellectual property rights of others.</p>
    </div>

    <div class="legal-section">
        <h2>4. Fair Use Notice</h2>
        <p>MaxPreps News operates as a news aggregation and sports information website. Much of our content consists of:</p>
        <ul>
            <li><strong>Transformative Rewriting:</strong> We rewrite and transform publicly available sports news into unique content</li>
            <li><strong>Fair Use Commentary:</strong> Analysis and commentary on publicly available sports events and data</li>
            <li><strong>Embedded Content:</strong> Properly embedded videos and media from authorized sources (NFHS Network, etc.)</li>
        </ul>
        <p>We believe our use of copyrighted material constitutes "fair use" under U.S. copyright law. However, we respect all intellectual property rights and will remove any content that infringes upon those rights.</p>
    </div>

    <div class="legal-section">
        <h2>5. Our Commitment</h2>
        <p>We are committed to:</p>
        <ul>
            <li>Responding to valid DMCA notices within 24-48 hours</li>
            <li>Removing infringing content promptly upon verification</li>
            <li>Notifying content uploaders of takedown requests</li>
            <li>Processing counter-notices fairly and promptly</li>
        </ul>
    </div>

    <div class="legal-contact">
        <h2>6. Contact Our DMCA Agent</h2>
        <p>All DMCA notices and counter-notices must be sent to our designated copyright agent:</p>
        <p>
            <strong>DMCA Agent</strong><br>
            MaxPreps News<br>
            Email: <a href="mailto:<?php echo htmlspecialchars($config['support_email'] ?? 'dmca@maxpreps.news'); ?>"><?php echo htmlspecialchars($config['support_email'] ?? 'dmca@maxpreps.news'); ?></a><br>
            Subject Line: "DMCA Takedown Request" or "DMCA Counter-Notice"
        </p>
        <p><em>Please note: Only DMCA notices should be sent to our copyright agent. All other inquiries should be directed to our general <a href="/contact.php">contact page</a>.</em></p>
    </div>

    <div class="legal-section">
        <h2>7. Misrepresentation</h2>
        <p>Under Section 512(f) of the DMCA, any person who knowingly materially misrepresents that material or activity is infringing, or that material or activity was removed or disabled by mistake or misidentification, may be subject to liability.</p>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>
