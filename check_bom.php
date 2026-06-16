<?php
$content = file_get_contents('c:/xampp/htdocs/maxpreps.news/go/sitemap.php');
if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
    echo "BOM FOUND\n";
    $content = substr($content, 3);
    file_put_contents('c:/xampp/htdocs/maxpreps.news/go/sitemap.php.clean', $content);
} else {
    echo "NO BOM\n";
}
echo "First 10 bytes: " . bin2hex(substr($content, 0, 10)) . "\n";
