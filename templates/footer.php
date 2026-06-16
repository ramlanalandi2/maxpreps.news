<?php
// templates/footer.php
$siteTitle = $config['site_title'] ?? 'MaxPreps News';
$supportEmail = $config['support_email'] ?? 'support@maxpreps.news';
$currentYear = date('Y');
$baseUrl = base_origin();
?>
<?php if (!empty($config['ads']['footer_top'])): ?>
    <div class="ad-container footer-top-ad" style="text-align:center; margin: 40px auto; max-width: 1200px;">
        <?php echo $config['ads']['footer_top']; ?>
    </div>
<?php endif; ?>

<footer class="site-footer">
    <div class="footer-content">
        <!-- About Section -->
        <div class="footer-section footer-about">
            <div class="footer-logo"><?php echo htmlspecialchars($siteTitle); ?></div>
            <p class="footer-description">Your source for high school sports news, highlights, and live game coverage. Powered by the NFHS Network.</p>
            <div class="footer-trust-badge">
                <span class="trust-text">Powered by</span>
                <span class="trust-brand">PlayOn! Sports</span>
                <svg viewBox="0 0 24 24" width="18" height="18" fill="#ffcc00">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="footer-section footer-links-section">
            <h3 class="footer-heading">Quick Links</h3>
            <nav class="footer-links" aria-label="Footer Navigation">
                <a href="/" aria-label="Home">Home</a>
                <a href="/player.php" aria-label="Live Games">Live Games</a>
                <a href="/#news" aria-label="Sports News">Sports News</a>
                <a href="/about.php" aria-label="About Us">About Us</a>
            </nav>
        </div>

        <!-- Legal Links -->
        <div class="footer-section footer-legal-section">
            <h3 class="footer-heading">Legal</h3>
            <nav class="footer-links" aria-label="Legal Links">
                <a href="/privacy.php" aria-label="Privacy Policy">Privacy Policy</a>
                <a href="/terms.php" aria-label="Terms of Use">Terms of Use</a>
                <a href="/dmca.php" aria-label="DMCA Notice">DMCA Notice</a>
                <a href="/contact.php" aria-label="Contact Support">Contact Support</a>
            </nav>
        </div>

        <!-- Contact Info -->
        <div class="footer-section footer-contact-section">
            <h3 class="footer-heading">Contact</h3>
            <div class="footer-contact-info">
                <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($supportEmail); ?>"><?php echo htmlspecialchars($supportEmail); ?></a></p>
                <p><strong>Support:</strong> <a href="/contact.php">Contact Form</a></p>
            </div>
        </div>
    </div>

    <!-- Copyright Bar -->
    <div class="footer-bottom">
        <div class="footer-copy">
            &copy; <?php echo $currentYear; ?> <?php echo htmlspecialchars($siteTitle); ?>. All Rights Reserved.
            <span class="footer-disclaimer">All trademarks are the property of their respective owners.</span>
        </div>
    </div>
</footer>

<!-- Structured Data for SEO -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "<?php echo htmlspecialchars($siteTitle); ?>",
  "url": "<?php echo $baseUrl; ?>",
  "logo": "<?php echo $baseUrl; ?>/favicon.ico",
  "description": "High school sports news, highlights, and live game coverage",
  "sameAs": [],
  "contactPoint": {
    "@type": "ContactPoint",
    "email": "<?php echo htmlspecialchars($supportEmail); ?>",
    "contactType": "Customer Support",
    "areaServed": "US"
  }
}
</script>

