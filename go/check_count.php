<?php
require_once 'c:\xampp\htdocs\maxpreps.news\go\db.php';
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM seo_keywords");
    $total = $stmt->fetchColumn();
    echo "Total Rows: " . $total;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
