<?php
$urls = [
    'https://maxpreps.news/sitemaps/sitemap_index.xml',
    'https://maxpreps.news/sitemaps/sitemap-1.xml',
    'https://maxpreps.news/go/sitemap.php',
    'https://maxpreps.news/sitemap-news.php',
    'https://maxpreps.news/player.php?event=gam0a522ba798'
];

foreach ($urls as $url) {
    echo "Testing $url...\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        echo "Error: $err\n";
    } else {
        echo "HTTP Code: $httpCode\n";
    }
    echo "-------------------\n";
}
