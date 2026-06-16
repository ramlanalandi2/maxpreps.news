<?php
require_once __DIR__ . '/fetch_news.php';

class TestManager extends NewsContentManager {
    public function testRefinementLocal($filePath) {
        echo "Reading Local File: $filePath\n";
        $html = file_get_contents($filePath);
        
        if (empty($html)) {
            echo "FAILED: File is empty or not found.\n";
            return;
        }
        
        echo "HTML read successfully (" . strlen($html) . " bytes)\n";

        $article = [
            'title' => 'Test Article',
            'thumbnail' => '',
            'url' => 'https://sports.yahoo.com/test',
            'key' => md5('https://sports.yahoo.com/test')
        ];
        
        // Mock og:image to trigger deduplication check
        // Let's find an img src in the file first to use as mock hero image
        if (preg_match('/<img[^>]+src="([^"]+)"/i', $html, $m)) {
             $mockHero = $m[1];
             echo "Mocking Hero Image as: $mockHero\n";
             // Inject og:image into HTML for the test
             $html = '<meta property="og:image" content="'.$mockHero.'">' . $html;
        }

        $enriched = $this->extractArticleContent($article, $html);
        
        echo "=== HERO IMAGE ===\n";
        echo $enriched['hero_image_url'] . "\n\n";
        
        echo "=== FILTERED CONTENT (Looking for images) ===\n";
        // Extract all img tags from content
        if (preg_match_all('/<img[^>]+>/i', $enriched['content'], $matches)) {
            foreach ($matches[0] as $img) {
                echo "FOUND IMG: $img\n";
                if (stripos($img, 'yahoo') !== false && stripos($img, 'logo') !== false) {
                    echo "  -> ERROR: Yahoo logo detected!\n";
                }
            }
        } else {
            echo "No images found in filtered content (This confirms deduplication if hero was first img).\n";
        }
        
        echo "\n=== RAW CONTENT PREVIEW ===\n";
        echo substr($enriched['raw_content'], 0, 500) . "...\n";
    }
}

$manager = new TestManager();
$testFile = __DIR__ . '/debug/article_08e9e3e965cd8ea267960d95236c3bb0.html';
$manager->testRefinementLocal($testFile);
