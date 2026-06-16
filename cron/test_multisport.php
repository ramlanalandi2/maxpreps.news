#!/usr/bin/env php
<?php
/**
 * Test Multi-Sport News Fetcher
 * This tests the rotation logic and sport category tracking
 */

require_once __DIR__ . '/fetch_news.php';

echo "=== Multi-Sport News Fetcher Test ===\n\n";

// Create manager instance
$manager = new NewsContentManager();

echo "Testing sport selection and rotation...\n";

// Access protected methods using reflection for testing
$reflection = new ReflectionClass($manager);

// Test 1: Select sports
$selectMethod = $reflection->getMethod('selectSportsToFetch');
$selectMethod->setAccessible(true);

echo "\n--- Test Run 1 ---\n";
$sports1 = $selectMethod->invoke($manager);
echo "Selected sports: " . implode(', ', $sports1) . "\n";
echo "Count: " . count($sports1) . " sports\n";

echo "\n--- Test Run 2 ---\n";
$sports2 = $selectMethod->invoke($manager);
echo "Selected sports: " . implode(', ', $sports2) . "\n";
echo "Count: " . count($sports2) . " sports\n";

echo "\n--- Test Run 3 ---\n";
$sports3 = $selectMethod->invoke($manager);
echo "Selected sports: " . implode(', ', $sports3) . "\n";
echo "Count: " . count($sports3) . " sports\n";

// Verify no duplicates between runs
$combined = array_merge($sports1, $sports2, $sports3);
echo "\n--- Rotation Summary ---\n";
echo "Total sports selected across 3 runs: " . count($combined) . "\n";
echo "Unique sports selected: " . count(array_unique($combined)) . "\n";

// Test 2: Display names
echo "\n--- Testing Display Names ---\n";
$displayMethod = $reflection->getMethod('getSportDisplayName');
$displayMethod->setAccessible(true);

$testSports = ['boys_basketball', 'girls_soccer', 'boys_football'];
foreach ($testSports as $sport) {
    $displayName = $displayMethod->invoke($manager, $sport);
    echo "$sport => $displayName\n";
}

// Test 3: Check rotation file
$rotationFile = '/home/u348050414/domains/maxpreps.news/public_html/data/sport_rotation.json';
if (file_exists($rotationFile)) {
    echo "\n--- Rotation File Contents ---\n";
    $data = json_decode(file_get_contents($rotationFile), true);
    echo "Date: " . ($data['date'] ?? 'N/A') . "\n";
    echo "Used sports: " . implode(', ', $data['used_sports'] ?? []) . "\n";
}

echo "\n✅ Multi-sport test completed!\n";
