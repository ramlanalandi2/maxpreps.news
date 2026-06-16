<?php
$_GET['id'] = 'gam077cb690f7';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';

try {
    include 'c:/xampp/htdocs/maxpreps.news/api/event.php';
} catch (Exception $e) {
    echo "\nEXCEPTION: " . $e->getMessage() . "\n";
}
