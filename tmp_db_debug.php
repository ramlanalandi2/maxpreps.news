<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "--- DB Connection Test ---\n";
require_once 'go/db.php';

try {
    echo "Base URL: " . $base_url . "\n";
    echo "Site Name: " . $site_name . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM seo_keywords");
    $count = $stmt->fetchColumn();
    echo "Count in seo_keywords: " . $count . "\n";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT * FROM seo_keywords LIMIT 3");
        echo "Sample data:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            print_r($row);
        }
    } else {
        echo "TABLE IS EMPTY!\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
