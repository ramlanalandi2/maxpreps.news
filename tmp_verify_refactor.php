<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once 'c:/xampp/htdocs/maxpreps.news/includes/event_functions.php';

$eventId = 'gam077cb690f7';
echo "Testing get_event_data_internal directly for $eventId...\n";

$start = microtime(true);
$result = get_event_data_internal($eventId);
$end = microtime(true);

if ($result) {
    echo "SUCCESS: Found event data.\n";
    echo "Title: " . ($result['item']['title'] ?? 'N/A') . "\n";
    echo "Source: " . ($result['source'] ?? 'N/A') . "\n";
    echo "Time: " . round($end - $start, 4) . "s\n";
} else {
    echo "FAILURE: Event data not found.\n";
}
