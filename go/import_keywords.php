<?php
/**
 * ONE-TIME DB IMPORT HELPER
 * Run this ONCE via browser: http://localhost/maxpreps.news/go/import_keywords.php
 * DELETE this file immediately after successful import.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(0); // No time limit for large SQL
ini_set('memory_limit', '512M');

$db_host = 'localhost';
$db_name = 'bray5937_keyword';
$db_user = 'root';
$db_pass = '';
$sqlFile = __DIR__ . '/keywords_data.sql';

echo "<pre>\n";
echo "=== Keywords DB Import Helper ===\n\n";

// Step 1: Check SQL file exists
if (!file_exists($sqlFile)) {
    die("❌ ERROR: keywords_data.sql not found at: $sqlFile\n");
}
$size = round(filesize($sqlFile) / 1024 / 1024, 1);
echo "✅ SQL file found: keywords_data.sql ({$size} MB)\n";

// Step 2: Connect without selecting DB (so we can CREATE it)
try {
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MAX_BUFFER_SIZE => 1024 * 1024 * 50, // 50MB buffer
    ]);
    echo "✅ Connected to MySQL\n";
} catch (PDOException $e) {
    die("❌ MySQL connection failed: " . $e->getMessage() . "\n");
}

// Step 3: Create DB if not exists
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db_name`");
    echo "✅ Database '$db_name' ready\n";
} catch (PDOException $e) {
    die("❌ Failed to create database: " . $e->getMessage() . "\n");
}

// Step 4: Read and execute SQL file in chunks
echo "⏳ Importing SQL (this may take several minutes)...\n";
flush();

$handle = fopen($sqlFile, 'r');
if (!$handle) {
    die("❌ Cannot open keywords_data.sql for reading\n");
}

$buffer = '';
$statementCount = 0;
$errorCount = 0;

while (!feof($handle)) {
    $line = fgets($handle);
    
    // Skip comments
    if (ltrim($line, " \t") === '' || strpos($line, '--') === 0 || strpos($line, '/*') === 0) {
        continue;
    }
    
    $buffer .= $line;
    
    // Execute when we hit a semicolon at end of line
    if (substr(rtrim($line), -1) === ';') {
        try {
            $pdo->exec($buffer);
            $statementCount++;
            
            // Progress every 1000 statements
            if ($statementCount % 1000 === 0) {
                echo "   ... {$statementCount} statements executed\n";
                flush();
            }
        } catch (PDOException $e) {
            $errorCount++;
            if ($errorCount <= 5) {
                echo "⚠️  Error on statement #{$statementCount}: " . substr($e->getMessage(), 0, 120) . "\n";
            }
        }
        $buffer = '';
    }
}
fclose($handle);

echo "\n✅ Import complete!\n";
echo "   Statements executed: {$statementCount}\n";
echo "   Errors (skipped): {$errorCount}\n\n";

// Step 5: Verify row count
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM seo_keywords");
    $count = $stmt->fetchColumn();
    echo "✅ seo_keywords table has: " . number_format($count) . " rows\n";
    echo "\nTotal pages in sitemap: " . ceil($count / 50000) . " pages × 50,000 URLs each\n";
} catch (PDOException $e) {
    echo "⚠️  Could not count rows: " . $e->getMessage() . "\n";
}

echo "\n⚠️  IMPORTANT: Delete this file now! It should not be publicly accessible.\n";
echo "   Run: del c:\\xampp\\htdocs\\maxpreps.news\\go\\import_keywords.php\n";
echo "</pre>\n";
