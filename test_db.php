<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "Starting diagnostic script..." . PHP_EOL;

require_once __DIR__ . '/includes/helpers.php';
$config = require __DIR__ . '/config.php';

$db_config = [
    'host' => 'localhost',
    'name' => 'bray5937_keyword',
    'user' => 'root',
    'pass' => '',
];

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ]
    );

    echo "Connected to DB." . PHP_EOL;

    $stmt = $pdo->query("SELECT COUNT(*) FROM seo_keywords");
    $count = $stmt->fetchColumn();
    echo "Keyword count in DB: " . $count . PHP_EOL;
    
    $stmt = $pdo->query("SELECT keyword, slug FROM seo_keywords LIMIT 5");
    while ($row = $stmt->fetch()) {
        echo " - " . $row['keyword'] . " -> " . $row['slug'] . PHP_EOL;
    }

} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . PHP_EOL;
}
