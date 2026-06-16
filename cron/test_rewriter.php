<?php
/**
 * SEO Sports Rewriter Test Script
 * Tests the integrated rewriter with sample content
 */

require_once __DIR__ . '/includes/SEOSportsRewriter.php';

echo "=== SEO SPORTS REWRITER TEST ===\n\n";

// Test Article 1: Game Report
$sample1 = "The Lincoln High School football team won their game against Washington High School on Friday night. The final score was 28-14. Quarterback John Smith scored two touchdowns and threw for 250 yards. The team played great defense in the second half. Coach Johnson was happy with the performance. Lincoln High School now has a record of 5-2 this season.";

// Test Article 2: Player Achievement
$sample2 = "Sarah Martinez broke the national record for 3-pointers in high school basketball. She hit seven triples in the game against rival Central High. Martinez now has scored 500 career points. The senior player is committed to UCLA.";

// Initialize rewriter
$rewriter = new SEOSportsRewriter();

// Define SEO keywords
$keywords = [
    'high school football',
    'varsity sports',
    'prep athletics',
    'basketball championship'
];

// Test 1: Game Report
echo "=== TEST 1: GAME REPORT ===\n";
echo "ORIGINAL:\n$sample1\n\n";

$rewritten1 = $rewriter->rewrite($sample1, $keywords);
echo "REWRITTEN:\n$rewritten1\n\n";

$uniqueness1 = $rewriter->calculateUniqueness($sample1, $rewritten1);
echo "UNIQUENESS SCORE: {$uniqueness1}%\n";
echo str_repeat("=", 60) . "\n\n";

// Test 2: Player Achievement
echo "=== TEST 2: PLAYER ACHIEVEMENT ===\n";
echo "ORIGINAL:\n$sample2\n\n";

$rewritten2 = $rewriter->rewrite($sample2, $keywords);
echo "REWRITTEN:\n$rewritten2\n\n";

$uniqueness2 = $rewriter->calculateUniqueness($sample2, $rewritten2);
echo "UNIQUENESS SCORE: {$uniqueness2}%\n";
echo str_repeat("=", 60) . "\n\n";;

// Test 3: Sentence-level transformation
echo "=== TEST 3: SENTENCE TRANSFORMATION ===\n";
$testSentence = "The quarterback scored 35 points and led his team to victory.";
echo "ORIGINAL SENTENCE:\n$testSentence\n\n";

$rewrittenSentence = $rewriter->rewriteSentenceAdvanced($testSentence);
echo "REWRITTEN SENTENCE:\n$rewrittenSentence\n\n";
echo str_repeat("=", 60) . "\n\n";

// Summary
echo "=== SUMMARY ===\n";
echo "Test 1 Uniqueness: {$uniqueness1}%\n";
echo "Test 2 Uniqueness: {$uniqueness2}%\n";
$avgUniqueness = ($uniqueness1 + $uniqueness2) / 2;
echo "Average Uniqueness: " . round($avgUniqueness, 2) . "%\n";

if ($avgUniqueness >= 90) {
    echo "\n✅ EXCELLENT! Target of 90%+ uniqueness achieved!\n";
} elseif ($avgUniqueness >= 85) {
    echo "\n✓ GOOD! Above 85% uniqueness\n";
} else {
    echo "\n⚠ Needs improvement. Target is 90%+\n";
}

echo "\n=== TEST COMPLETE ===\n";
