<?php
// Configuration for Native PHP SEO Scripts
$db_host = 'localhost';
$db_name = 'bray5937_keyword';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    header('Content-Type: text/plain');
    die("Database Connection Error: " . $e->getMessage());
}

// Global Site Name
$site_name = "Highschool Live Streams";
$base_url = "https://maxpreps.news/go"; 
