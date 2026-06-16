<?php

function getTrendingKeywords() {
    $cacheFile = __DIR__ . '/../cache/trending.json';
    $cacheTime = 1800; // 30 menit

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    $rss = @simplexml_load_file("https://www.bing.com/news/trendingtopics?format=rss");

    $keywords = [];

    if ($rss && isset($rss->channel->item)) {
        foreach ($rss->channel->item as $item) {
            $keywords[] = (string)$item->title;
        }
    }

    file_put_contents($cacheFile, json_encode($keywords));

    return $keywords;
}