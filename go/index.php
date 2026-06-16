<?php
/**
 * Standalone SEO Index Point
 * This file handles dynamic routing for the doorway pages.
 */

// 1. Hide the architecture: Prevent direct access via the raw query string
if (strpos($_SERVER['REQUEST_URI'], 'index.php?slug=') !== false) {
    // If someone types index.php?slug=keyword, force them to the clean /go/keyword URL
    $cleanSlug = preg_replace('/[^a-zA-Z0-9-_]/', '', $_GET['slug']);
    $cleanUrl = 'https://maxpreps.news/go/' . $cleanSlug;
    header("Location: " . $cleanUrl, true, 301);
    exit;
}

// 2. Process the request safely
if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    // Sanitize input: Strip out anything that isn't a letter, number, dash, or underscore
    // This prevents directory traversal attacks (like passing ../../) and SQL injection attempts
    $slug = preg_replace('/[^a-zA-Z0-9-_]/', '', $_GET['slug']); 
    
    require_once 'doorway.php';
    serveDoorway($slug);
} else {
    // Normal redirect for humans or bots hitting the base /go/ folder without a slug
    header("Location: https://maxpreps.news/player.php", true, 301);
    exit;
}