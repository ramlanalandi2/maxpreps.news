<?php

/**
 * Sitemap Generator - Fixed Version
 * Fixes: BOM-safe, proper error codes, consistent pagination
 */

// Must be absolute first line - capture everything including potential stray output
ob_start();

error_reporting(0);
ini_set('display_errors', '0');

// ============================================================
// CONFIG
// ============================================================
$base_url = 'https://maxpreps.news/go';
$db_host  = 'localhost';
$db_name  = 'bray5937_keyword';
$db_user  = 'root';
$db_pass  = '';
$limit    = 10000;

// ============================================================
// HELPER: Send error response (503 = GSC will retry later)
// ============================================================
function send_error_sitemap(string $message = 'Service Unavailable'): void
{
    ob_end_clean();
    if (!headers_sent()) {
        http_response_code(503);
        header('Retry-After: 3600');
        header('Content-Type: application/xml; charset=utf-8');
    }
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<!-- Error: ' . htmlspecialchars($message, ENT_XML1) . ' -->' . "\n";
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>';
    exit;
}

// ============================================================
// DB CONNECTION
// ============================================================
try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ]
    );
} catch (PDOException $e) {
    send_error_sitemap('DB connection failed');
}

// ============================================================
// ROUTING
// ============================================================
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 0;

// Discard ALL buffered output (BOM, whitespace, anything)
ob_end_clean();

// Send correct header
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex'); // Sitemap itself doesn't need to be indexed

// ============================================================
// SITEMAP INDEX (no ?p param)
// ============================================================
if ($page === 0) {
    try {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM seo_keywords")->fetchColumn();
    } catch (PDOException $e) {
        $total = 0;
    }

    $totalPages = ($total > 0) ? (int)ceil($total / $limit) : 0;

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    for ($i = 1; $i <= $totalPages; $i++) {
        $loc = htmlspecialchars($base_url . '/sitemap.php?p=' . $i, ENT_XML1, 'UTF-8');
        echo "  <sitemap>\n";
        echo "    <loc>{$loc}</loc>\n";
        echo '    <lastmod>' . date('Y-m-d') . "</lastmod>\n";
        echo "  </sitemap>\n";
    }

    echo '</sitemapindex>';
    exit;
}

// ============================================================
// CHILD SITEMAP (?p=N)
// ============================================================
$offset = ($page - 1) * $limit;

try {
    // ✅ ORDER BY id → consistent pagination, no duplicate/missing URLs
    $stmt = $pdo->prepare(
        "SELECT slug FROM seo_keywords ORDER BY id ASC LIMIT ? OFFSET ?"
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $urls = $stmt->fetchAll();
} catch (PDOException $e) {
    // ✅ Return 503 instead of empty XML → GSC will retry
    http_response_code(503);
    header('Retry-After: 3600');
    $urls = [];
}

// ✅ If page requested doesn't exist, return 404
if (empty($urls) && $page > 1) {
    http_response_code(404);
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $row) {
    // ✅ Extra safety: clean slug before output
    $slug = ltrim(trim($row['slug']), '/');
    $loc  = htmlspecialchars($base_url . '/' . $slug, ENT_XML1, 'UTF-8');
    echo "  <url>\n";
    echo "    <loc>{$loc}</loc>\n";
    echo '    <lastmod>' . date('Y-m-d') . "</lastmod>\n";
    echo "    <changefreq>daily</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
