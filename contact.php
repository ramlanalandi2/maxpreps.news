<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
$config = require __DIR__ . '/config.php';

$pageTitle = 'Contact Support';
$metaDescription = 'Contact MaxPreps News support team for questions, feedback, or assistance.';

// Handle form submission
$messageSent = false;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $errorMessage = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } else {
        // Send email (basic implementation)
        $to = $config['support_email'] ?? 'support@maxpreps.news';
        $emailSubject = "[Contact Form] " . htmlspecialchars($subject);
        $emailBody = "Name: " . htmlspecialchars($name) . "\n";
        $emailBody .= "Email: " . htmlspecialchars($email) . "\n\n";
        $emailBody .= "Message:\n" . htmlspecialchars($message);
        $headers = "From: " . htmlspecialchars($email) . "\r\n";
        $headers .= "Reply-To: " . htmlspecialchars($email);
        
        if (@mail($to, $emailSubject, $emailBody, $headers)) {
            $messageSent = true;
        } else {
            $errorMessage = 'Failed to send message. Please try emailing us directly.';
        }
    }
}
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
    <link rel="canonical" href="<?php echo base_origin() . SITE_PATH; ?>contact.php">
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/common.css">
    <style>
        .contact-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .contact-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .contact-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #1a1a1a;
        }
        .contact-header p {
            color: #666;
            font-size: 1.1rem;
        }
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .contact-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .contact-card h3 {
            color: #0066cc;
            margin-bottom: 10px;
        }
        .contact-card p {
            color: #666;
            margin-bottom: 10px;
        }
        .contact-card a {
            color: #0066cc;
            text-decoration: none;
        }
        .contact-card a:hover {
            text-decoration: underline;
        }
        .contact-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            font-family: inherit;
        }
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        .submit-btn {
            background: #0066cc;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 600;
        }
        .submit-btn:hover {
            background: #0052a3;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/templates/header.php'; ?>

<div class="contact-container">
    <div class="contact-header">
        <h1>Contact Support</h1>
        <p>We're here to help! Get in touch with our team.</p>
    </div>

    <div class="contact-grid">
        <div class="contact-card">
            <h3>📧 Email Support</h3>
            <p>For general inquiries:</p>
            <a href="mailto:<?php echo htmlspecialchars($config['support_email'] ?? 'support@maxpreps.news'); ?>">
                <?php echo htmlspecialchars($config['support_email'] ?? 'support@maxpreps.news'); ?>
            </a>
        </div>
        
        <div class="contact-card">
            <h3>⏰ Response Time</h3>
            <p>We typically respond within:</p>
            <strong>24-48 hours</strong>
        </div>
        
        <div class="contact-card">
            <h3>🔒 Privacy</h3>
            <p>Your information is secure</p>
            <a href="/privacy.php">Privacy Policy</a>
        </div>
    </div>

    <div class="contact-form">
        <h2 style="margin-bottom: 20px;">Send Us a Message</h2>
        
        <?php if ($messageSent): ?>
            <div class="success-message">
                <strong>Success!</strong> Your message has been sent. We'll get back to you soon.
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="error-message">
                <strong>Error:</strong> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/contact.php">
            <div class="form-group">
                <label for="name">Your Name *</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Your Email *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="subject">Subject *</label>
                <select id="subject" name="subject" required>
                    <option value="">-- Select a topic --</option>
                    <option value="General Inquiry">General Inquiry</option>
                    <option value="Technical Support">Technical Support</option>
                    <option value="Content Issue">Report Content Issue</option>
                    <option value="Copyright/DMCA">Copyright/DMCA</option>
                    <option value="Advertising">Advertising Inquiry</option>
                    <option value="Partnership">Partnership Opportunity</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="message">Message *</label>
                <textarea id="message" name="message" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            </div>
            
            <button type="submit" class="submit-btn">Send Message</button>
        </form>
    </div>

    <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <h3>Frequently Asked Questions</h3>
        <p><strong>Q: How can I report incorrect information in an article?</strong><br>
        A: Use the contact form above with subject "Content Issue" and include the article URL.</p>
        
        <p><strong>Q: How do I submit a DMCA takedown request?</strong><br>
        A: Please visit our <a href="/dmca.php">DMCA Notice page</a> for detailed instructions.</p>
        
        <p><strong>Q: Can I use your content on my website?</strong><br>
        A: Please review our <a href="/terms.php">Terms of Use</a> and contact us for permissions.</p>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>
