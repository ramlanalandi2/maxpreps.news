<?php
// config.php
return [
    // Site Identity
    'site_title' => 'HighSchoolNews',
    'site_tagline' => 'High School Sports News & Verified Streams',
    
    // Logo Text Parts (Sports<span>Media</span>TV)
    'site_logo_text_1' => 'High',
    'site_logo_text_2' => 'School',
    'site_logo_text_3' => 'News',
    
    // Contact
    'support_email' => 'support@maxpreps.news',

    // Domains
    'site_name' => 'MaxPreps News',
    'site_domain' => 'maxpreps.news',
    'maxpreps_base_url' => 'https://www.maxpreps.com',
    'yahoo_base_url' => 'https://sports.yahoo.com',
    'maxpreps_image_url' => 'https://image.maxpreps.io',
    'allowed_domains' => [
        'maxpreps.news',
        'www.maxpreps.news',
        'www1.maxpreps.news',
    ],
    
    'allowed_hls_domains' => ['maxpreps.io', 'maxpreps.com', 'nfhsnetwork.com'],
    'proxy_referer' => 'https://www.maxpreps.com/',
    'default_share_image' => 'https://social.nfhsnetwork.com/default_share.png',
    'default_thumbnail' => 'https://maxpreps.news/assets/images/default-sports.png',
    'social_links' => [
        'facebook' => 'https://www.facebook.com/NFHSNetwork',
        'twitter' => 'https://twitter.com/NFHSNetwork',
        'instagram' => 'https://www.instagram.com/nfhsnetwork',
    ],
    
    // Ad Placements (Insert HTML/JS codes here)
    'ads' => [
        'header_top' => '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8973762345950558"
     crossorigin="anonymous"></script>',          // Appears above the navigation bar (all pages)
        'sidebar_top' => "",         // Disabled for AdSense Approval
        'sidebar_bottom' => "", // Disabled for AdSense Approval
        'feed_middle' => "", // Disabled for AdSense Approval
        'upcoming_middle' => "", // Disabled for AdSense Approval
        'article_middle' => "",      // Disabled for AdSense Approval
        'footer_top' => "",          // Disabled for AdSense Approval
        
        // Player Page Ads
        'player_below' => "",        // Disabled for AdSense Approval
        'player_chat' => "",         // Disabled for AdSense Approval
        'player_schedule' => "",     // Disabled for AdSense Approval
        'player_footer' => "",       // Disabled for AdSense Approval
    ]
];
