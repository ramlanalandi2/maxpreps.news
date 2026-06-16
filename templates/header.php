<?php
// templates/header.php
$siteTitle = $config['site_title'] ?? 'HighSchoolNews';
$logoText1 = $config['site_logo_text_1'] ?? 'Sports';
$logoText2 = $config['site_logo_text_2'] ?? 'Media';
$logoText3 = $config['site_logo_text_3'] ?? 'TV';
?>
<header class="site-header">
    <?php if (!empty($config['ads']['header_top'])): ?>
        <div class="ad-container header-top-ad" style="text-align:center; padding: 10px 0;">
            <?php echo $config['ads']['header_top']; ?>
        </div>
    <?php endif; ?>
    <div class="header-inner">
        <a href="<?php echo SITE_PATH; ?>" class="site-logo"><?php echo $logoText1; ?><span><?php echo $logoText2; ?></span><?php echo $logoText3; ?></a>
        
        <!-- Search Form -->
        <form action="<?php echo SITE_PATH; ?>search.php" method="GET" class="header-search" role="search" aria-label="Site search">
            <input 
                type="search" 
                name="q" 
                class="search-input" 
                placeholder="Search sports news..." 
                aria-label="Search" 
                autocomplete="off"
                value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>"
            >
            <button type="submit" class="search-button" aria-label="Submit search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </button>
        </form>
        
        <nav class="nav-links">
            <a href="<?php echo SITE_PATH; ?>">Home</a>
            <a href="#news">News</a>
            <a href="#upcoming">Upcoming</a>
        </nav>
    </div>
</header>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "<?php echo htmlspecialchars($siteTitle); ?>",
  "url": "<?php echo base_origin() . SITE_PATH; ?>",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "<?php echo base_origin() . SITE_PATH; ?>search.php?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>
<!-- Google Custom Search Engine -->
<script async src="https://cse.google.com/cse.js?cx=a6b03b40430634436"></script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "<?php echo htmlspecialchars($siteTitle); ?>",
  "url": "<?php echo base_origin() . SITE_PATH; ?>",
  "logo": "<?php echo $config['default_share_image'] ?? 'https://social.nfhsnetwork.com/default_share.png'; ?>",
  "sameAs": [
    "<?php echo $config['social_links']['facebook'] ?? 'https://www.facebook.com/NFHSNetwork'; ?>",
    "<?php echo $config['social_links']['twitter'] ?? 'https://twitter.com/NFHSNetwork'; ?>",
    "<?php echo $config['social_links']['instagram'] ?? 'https://www.instagram.com/nfhsnetwork'; ?>"
  ]
}
</script>
<script async type="application/javascript"
        src="https://news.google.com/swg/js/v1/swg-basic.js"></script>
<script>
  (self.SWG_BASIC = self.SWG_BASIC || []).push( basicSubscriptions => {
    basicSubscriptions.init({
      type: "NewsArticle",
      isPartOfType: ["Product"],
      isPartOfProductId: "CAowtsHEDA:openaccess",
      clientOptions: { theme: "light", lang: "en" },
    });
  });
</script>
<!-- AdSense Bot Compliance: Removed silkyincrease script -->
<!-- Removed -->
<!-- Removed -->
<!-- Removed Ezoic -->
<!-- Removed ezstandalone -->
<!-- Removed Ezoic Analytics -->
<!-- Removed Ezoic Placeholder -->
