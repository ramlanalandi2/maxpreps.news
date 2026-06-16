<?php
declare(strict_types=1);

// ✅ Disable error display in API to prevent JSON pollution
ini_set('display_errors', '0');
error_reporting(E_ALL);

// ✅ PERFORMANCE: Start output buffering to prevent accidental output pollution
ob_start();

// ✅ PERFORMANCE: Load core logic
require_once __DIR__ . '/../includes/event_functions.php';

$eventId = $_GET['id'] ?? '';
// Allow forward slash for event IDs like "school-name/gam123"
$eventId = preg_replace('/[^a-zA-Z0-9_\/-]/', '', $eventId);

if ($eventId === '') {
    send_json_response(['error' => 'missing id parameter'], 400);
}

// ✅ Use central logic function
$result = get_event_data_internal($eventId);

if ($result) {
    send_json_response($result);
}

// ✅ Log failure for diagnosis
$logMsg = date('[Y-m-d H:i:s] ') . "404 - Event Not Found: {$eventId}\n";
@file_put_contents(__DIR__ . '/../api_errors.log', $logMsg, FILE_APPEND);

send_json_response([
    'error' => 'event not found', 
    'id' => $eventId,
    'note' => 'Event may have expired, not recorded, or ID format may be incorrect'
], 404);

function send_json_response(array $data, int $code = 200): void 
{
    if (ob_get_level()) ob_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

// ✅ OPTIMIZED: Cached fetchJson with shorter TTL to handle 503 errors
function fetchJson(string $url): ?array
{
    $cacheKey = get_cache_key($url);
    $cached = cache_get($cacheKey, 30); // Short cache to avoid stale data
    if ($cached !== null && isset($cached['data'])) {
        return $cached['data'];
    }
    
    $response = fetchContent($url, ['Accept: application/json'], 2);
    if ($response === null) {
        return null;
    }
    
    $data = json_decode($response, true);
    if (!is_array($data)) {
        return null;
    }
    
    cache_set($cacheKey, $data, 30); // Short cache
    return $data;
}

function fetchContent(string $url, array $headers = [], int $retries = 1): ?string
{
    // ✅ FIX: Reduced retries to minimize 503 errors - fail fast
    // Only retry once for connection errors, not for 503s
    for ($attempt = 0; $attempt <= $retries; $attempt++) {
        if ($attempt > 0) {
            // ✅ FIX: Short delay only for connection errors (not 503s)
            usleep(200000); // 0.2 second delay
        }
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 8, // ✅ FIX: Reduced timeout - fail fast
            CURLOPT_CONNECTTIMEOUT => 3, // ✅ FIX: Reduced connection timeout
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array_merge(
                [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept: text/html,application/json;q=0.9,*/*;q=0.8',
                ],
                $headers
            ),
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // Force IPv4 for Windows stability
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // ✅ FIX: Don't retry on 503/502/504 - fail immediately to avoid overwhelming server
        if ($httpCode === 503 || $httpCode === 502 || $httpCode === 504) {
            $logMsg = date('[Y-m-d H:i:s] ') . "API Error: HTTP {$httpCode} for URL: {$url}\n";
            @file_put_contents(__DIR__ . '/../api_errors.log', $logMsg, FILE_APPEND);
            return null;
        }

        if ($response === false || !empty($curlError)) {
            $logMsg = date('[Y-m-d H:i:s] ') . "CURL Error: {$curlError} for URL: {$url}\n";
            @file_put_contents(__DIR__ . '/../api_errors.log', $logMsg, FILE_APPEND);
            if ($attempt < $retries) {
                continue; 
            }
            return null;
        }

        if ($httpCode >= 400) {
            $logMsg = date('[Y-m-d H:i:s] ') . "API Error: HTTP {$httpCode} for URL: {$url}\n";
            @file_put_contents(__DIR__ . '/../api_errors.log', $logMsg, FILE_APPEND);
            return null; 
        }

        return $response;
    }
    
    return null;
}

// ✅ PERFORMANCE: Cached fetchEventDetails dengan TTL lebih lama
function fetchEventDetails(string $url): array
{
    if ($url === '' || stripos($url, 'nfhsnetwork.com') === false) {
        return [];
    }

    // Check cache first
    $cacheKey = 'event_details_' . md5($url);
    $cached = cache_get($cacheKey, 1800); // 30 menit cache untuk event details
    if ($cached !== null && isset($cached['data'])) {
        return $cached['data'];
    }

    $html = fetchContent($url, ['Accept: text/html']);
    if ($html === null) {
        return [];
    }

    $ogMeta = [
        'title' => extract_meta_content($html, 'og:title'),
        'description' => extract_meta_content($html, 'og:description'),
        'image' => extract_meta_content($html, 'og:image'),
    ];

    $detail = [];

    // ✅ NEW: Try to extract from structured data JSON-LD first
    if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
    foreach ($matches[1] as $block) {
        $block = trim($block);
        $decoded = json_decode($block, true);
        if (!is_array($decoded)) {
            continue;
        }

        $candidates = isset($decoded[0]) ? $decoded : [$decoded];
        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            $type = $candidate['@type'] ?? '';
            if ($type !== 'Event' && $type !== 'SportsEvent') {
                continue;
            }

            $detail = sanitizeDetail($candidate);
            foreach ($ogMeta as $key => $value) {
                if ($value && empty($detail[$key])) {
                    $detail[$key] = $value;
                }
            }
                break 2; // Exit both loops
            }
        }
    }

    // ✅ NEW: Fallback: Extract from teams-section HTML if structured data not available
    if (empty($detail['home_team']) || empty($detail['away_team']) || empty($detail['home_logo']) || empty($detail['away_logo'])) {
        $teamsData = extractTeamsFromHtml($html);
        if (!empty($teamsData)) {
            if (empty($detail['home_team']) && !empty($teamsData['home_team'])) {
                $detail['home_team'] = $teamsData['home_team'];
        }
            if (empty($detail['away_team']) && !empty($teamsData['away_team'])) {
                $detail['away_team'] = $teamsData['away_team'];
            }
            if (empty($detail['home_logo']) && !empty($teamsData['home_logo'])) {
                $detail['home_logo'] = $teamsData['home_logo'];
            }
            if (empty($detail['away_logo']) && !empty($teamsData['away_logo'])) {
                $detail['away_logo'] = $teamsData['away_logo'];
            }
        }
    }

    // ✅ NEW: Fallback: Extract date-time from HTML if structured data not available
    if (empty($detail['startDate'])) {
        $dateTimeData = extractDateTimeFromHtml($html);
        if (!empty($dateTimeData['startDate'])) {
            $detail['startDate'] = $dateTimeData['startDate'];
        }
    }

    // ✅ NEW: Fallback: Extract location from HTML if structured data not available
    if (empty($detail['city']) && empty($detail['state']) && empty($detail['venue'])) {
        $locationData = extractLocationFromHtml($html);
        if (!empty($locationData)) {
            if (empty($detail['city']) && !empty($locationData['city'])) {
                $detail['city'] = $locationData['city'];
            }
            if (empty($detail['state']) && !empty($locationData['state'])) {
                $detail['state'] = $locationData['state'];
            }
            if (empty($detail['venue']) && !empty($locationData['venue'])) {
                $detail['venue'] = $locationData['venue'];
            }
        }
    }

    // Merge with ogMeta if detail is empty
    if (empty($detail)) {
        $detail = $ogMeta;
    } else {
        foreach ($ogMeta as $key => $value) {
            if ($value && empty($detail[$key])) {
                $detail[$key] = $value;
            }
        }
    }

    // ✅ FIX: Always return at least og:meta data if available, even if structured data is missing
    // This ensures we can detect that the page exists even with minimal data
    $result = array_filter($detail, static fn($value) => !empty($value));
    
    // ✅ FIX: If we have at least title or description from og:meta, consider it valid
    // This handles cases where page exists but structured data extraction failed
    if (!empty($result['title']) || !empty($result['description']) || !empty($result['image'])) {
        // ✅ PERFORMANCE: Cache event details untuk request berikutnya
        cache_set($cacheKey, $result, 1800);
        return $result;
    }
    
    // If we have no data at all, return empty array
    // ✅ PERFORMANCE: Cache empty result too (shorter TTL) to avoid repeated failed requests
    cache_set($cacheKey, [], 300); // 5 minutes for failed extractions
    return [];
}

// ✅ NEW: Extract team names and logos from teams-section HTML
function extractTeamsFromHtml(string $html): array
{
    $result = [
        'home_team' => null,
        'away_team' => null,
        'home_logo' => null,
        'away_logo' => null,
    ];

    // Extract teams-section using balanced div matching
    if (!preg_match('/<div[^>]*class=["\'][^"\']*teams-section[^"\']*["\'][^>]*>/is', $html, $startMatch, PREG_OFFSET_CAPTURE)) {
        return $result;
    }

    $startPos = $startMatch[0][1] + strlen($startMatch[0][0]);
    $depth = 1;
    $i = $startPos;
    $teamsSection = '';

    // Find matching closing div for teams-section
    while ($i < strlen($html) && $depth > 0) {
        if (substr($html, $i, 4) === '<div') {
            $depth++;
        } elseif (substr($html, $i, 6) === '</div>') {
            $depth--;
            if ($depth === 0) {
                $teamsSection = substr($html, $startPos, $i - $startPos);
                break;
            }
        }
        $i++;
    }

    if (empty($teamsSection)) {
        return $result;
    }

    // ✅ PRIORITY: Extract from organization-card structure (most reliable)
    // Extract all organization-card divs with balanced matching
    $cardPattern = '/<div[^>]*data-test=["\']organization-card["\'][^>]*>/is';
    $cardPositions = [];
    $offset = 0;
    
    while (preg_match($cardPattern, $teamsSection, $match, PREG_OFFSET_CAPTURE, $offset)) {
        $cardStart = $match[0][1];
        $cardStartPos = $cardStart + strlen($match[0][0]);
        $cardDepth = 1;
        $i = $cardStartPos;
        
        // Find matching closing div for this organization-card
        while ($i < strlen($teamsSection) && $cardDepth > 0) {
            if (substr($teamsSection, $i, 4) === '<div') {
                $cardDepth++;
            } elseif (substr($teamsSection, $i, 6) === '</div>') {
                $cardDepth--;
                if ($cardDepth === 0) {
                    $cardHtml = substr($teamsSection, $cardStartPos, $i - $cardStartPos);
                    $cardPositions[] = $cardHtml;
                    break;
                }
            }
            $i++;
        }
        
        $offset = $cardStart + 1;
        if ($offset >= strlen($teamsSection)) break;
    }
    
    if (!empty($cardPositions)) {
        $teams = [];
        foreach ($cardPositions as $cardHtml) {
            $teamData = ['name' => null, 'logo' => null];
            
            // Extract team name from data-test="title" (handle HTML comments like <!--[-->Team<!--]-->)
            if (preg_match('/<p[^>]*data-test=["\']title["\'][^>]*>(.*?)<\/p>/is', $cardHtml, $nameMatch)) {
                $teamName = $nameMatch[1];
                // Remove HTML comments like <!--[--> and <!--]-->
                $teamName = preg_replace('/<!--\[?-->|<!--\]?-->/s', '', $teamName);
                // Strip HTML tags
                $teamName = strip_tags($teamName);
                $teamName = trim($teamName);
                // Accept team name if not empty and not "vs" or "vs."
                // Note: "Away" is a valid team name from HTML, so we accept it
                if (!empty($teamName) && 
                    strtolower($teamName) !== 'vs' && 
                    strtolower($teamName) !== 'vs.' &&
                    strlen($teamName) > 0) {
                    $teamData['name'] = $teamName;
                }
            }
            
            // Extract logo from img src in logo-container or directly in card
            // Try logo-container first, then direct img tag
            if (preg_match('/<div[^>]*data-test=["\']logo-container["\'][^>]*>.*?<img[^>]*src=["\']([^"\']+)["\']/is', $cardHtml, $logoMatch) ||
                preg_match('/<img[^>]*src=["\']([^"\']+logo[^"\']*)["\'][^>]*>/is', $cardHtml, $logoMatch) ||
                preg_match('/<img[^>]*src=["\']([^"\']*nfhsnetwork\.com[^"\']*)["\'][^>]*>/is', $cardHtml, $logoMatch)) {
                $logoUrl = trim($logoMatch[1]);
                // Decode HTML entities (handle &amp; etc)
                $logoUrl = html_entity_decode($logoUrl, ENT_QUOTES | ENT_HTML5);
                // Only accept valid logo URLs
                if (!empty($logoUrl) && 
                    (stripos($logoUrl, 'font-logo') !== false || 
                     stripos($logoUrl, 'cfunity-school-logos') !== false ||
                     stripos($logoUrl, 'nfhsnetwork.com') !== false)) {
                    $teamData['logo'] = $logoUrl;
                }
            }
            
            // Only add team if we have at least name or logo
            if (!empty($teamData['name']) || !empty($teamData['logo'])) {
                $teams[] = $teamData;
            }
        }
        
        // Remove duplicates (same team might appear multiple times in mobile/desktop versions)
        $uniqueTeams = [];
        foreach ($teams as $team) {
            $key = ($team['name'] ?? '') . '|' . ($team['logo'] ?? '');
            if (!isset($uniqueTeams[$key])) {
                $uniqueTeams[$key] = $team;
            }
        }
        $teams = array_values($uniqueTeams);
        
        // Assign teams: first is away, second is home (based on HTML structure)
        if (count($teams) >= 2) {
            $result['away_team'] = $teams[0]['name'] ?? null;
            $result['away_logo'] = $teams[0]['logo'] ?? null;
            $result['home_team'] = $teams[1]['name'] ?? null;
            $result['home_logo'] = $teams[1]['logo'] ?? null;
        } elseif (count($teams) === 1) {
            // If only one team found, assign to home
            $result['home_team'] = $teams[0]['name'] ?? null;
            $result['home_logo'] = $teams[0]['logo'] ?? null;
        }
    }

    // Fallback: Extract team names from all data-test="title" if organization-card method failed
    if (empty($result['home_team']) && empty($result['away_team'])) {
        if (preg_match_all('/<p[^>]*data-test=["\']title["\'][^>]*>([^<]+)<\/p>/is', $teamsSection, $titleMatches)) {
            $titles = array_map('trim', $titleMatches[1]);
            // Filter out empty and generic names
            $titles = array_filter($titles, function($title) {
                return !empty($title) && 
                       strtolower($title) !== 'vs' && 
                       strtolower($title) !== 'vs.' &&
                       strtolower($title) !== 'home' &&
                       strtolower($title) !== 'away';
            });
            
            $titles = array_values($titles); // Re-index
            
            if (count($titles) >= 2) {
                $result['away_team'] = $titles[0];
                $result['home_team'] = $titles[1];
            } elseif (count($titles) === 1) {
                $result['home_team'] = $titles[0];
            }
        }
    }

    // Fallback: Extract logos from all img tags if organization-card method failed
    if (empty($result['home_logo']) && empty($result['away_logo'])) {
        if (preg_match_all('/<img[^>]*src=["\']([^"\']+)["\'][^>]*>/is', $teamsSection, $logoMatches)) {
            $logos = array_filter($logoMatches[1], function($logo) {
                return !empty($logo) && 
                       (stripos($logo, 'font-logo') !== false || 
                        stripos($logo, 'cfunity-school-logos') !== false ||
                        stripos($logo, 'nfhsnetwork.com') !== false);
            });
            
            $logos = array_values($logos); // Re-index
            
            if (count($logos) >= 2) {
                $result['away_logo'] = $logos[0];
                $result['home_logo'] = $logos[1];
            } elseif (count($logos) === 1) {
                $result['home_logo'] = $logos[0];
            }
        }
    }

    return array_filter($result, static fn($value) => !empty($value));
}

// ✅ NEW: Extract date-time from HTML (fallback if structured data not available)
function extractDateTimeFromHtml(string $html): array
{
    $result = [
        'startDate' => null,
    ];

    // Extract date-time from <div class="date-time"> or <div class="date-time-location">
    // Pattern: <div class="date-time">Dec 14, 2025 | 6:00 AM PST</div>
    if (preg_match('/<div[^>]*class=["\'][^"\']*date-time[^"\']*["\'][^>]*>(.*?)<\/div>/is', $html, $dateTimeMatch)) {
        $dateTimeText = $dateTimeMatch[1];
        // Remove HTML comments and tags
        $dateTimeText = preg_replace('/<!--.*?-->/s', '', $dateTimeText);
        $dateTimeText = strip_tags($dateTimeText);
        // Clean up delimiters and whitespace
        $dateTimeText = preg_replace('/\s*\|\s*/', ' | ', $dateTimeText);
        $dateTimeText = preg_replace('/\s+/', ' ', trim($dateTimeText));
        
        if (!empty($dateTimeText)) {
            // Try to parse date-time string like "Dec 14, 2025 | 6:00 AM PST"
            // Split by "|" to get date and time parts
            $parts = preg_split('/\s*\|\s*/', $dateTimeText, 2);
            $datePart = trim($parts[0] ?? '');
            $timePart = trim($parts[1] ?? '');
            
            if (!empty($datePart)) {
                // Extract timezone abbreviation (PST, EST, etc.) from time part first
                $timezoneAbbr = null;
                $tzMap = [
                    'PST' => 'America/Los_Angeles',
                    'PDT' => 'America/Los_Angeles',
                    'EST' => 'America/New_York',
                    'EDT' => 'America/New_York',
                    'CST' => 'America/Chicago',
                    'CDT' => 'America/Chicago',
                    'MST' => 'America/Denver',
                    'MDT' => 'America/Denver',
                    'AKST' => 'America/Anchorage',
                    'AKDT' => 'America/Anchorage',
                    'HST' => 'Pacific/Honolulu',
                    'GMT' => 'UTC',
                    'UTC' => 'UTC',
                ];
                
                if (preg_match('/\s+([A-Z]{2,4})$/i', $timePart, $tzMatch)) {
                    $timezoneAbbr = strtoupper(trim($tzMatch[1]));
                }
                
                $targetTimezone = ($timezoneAbbr && isset($tzMap[$timezoneAbbr])) 
                    ? $tzMap[$timezoneAbbr] 
                    : 'UTC';
                
                // Try to parse date (e.g., "Dec 14, 2025")
                try {
                    // Try common date formats
                    $dateFormats = [
                        'M j, Y',      // Dec 14, 2025
                        'M d, Y',      // Dec 14, 2025
                        'F j, Y',      // December 14, 2025
                        'F d, Y',      // December 14, 2025
                        'Y-m-d',       // 2025-12-14
                        'm/d/Y',       // 12/14/2025
                    ];
                    
                    $parsedDate = null;
                    foreach ($dateFormats as $format) {
                        $dt = DateTime::createFromFormat($format, $datePart, new DateTimeZone($targetTimezone));
                        if ($dt !== false) {
                            $parsedDate = $dt;
                            break;
                        }
                    }
                    
                    // If date parsing failed, try strtotime as fallback
                    if (!$parsedDate) {
                        $timestamp = strtotime($datePart . ' ' . $targetTimezone);
                        if ($timestamp !== false) {
                            $parsedDate = new DateTime('@' . $timestamp);
                            $parsedDate->setTimezone(new DateTimeZone($targetTimezone));
                        }
                    }
                    
                    // If we have time part, try to parse it
                    if ($parsedDate && !empty($timePart)) {
                        // Remove timezone abbreviation from time part for parsing
                        $timeOnly = preg_replace('/\s+[A-Z]{2,4}$/i', '', $timePart);
                        $timeOnly = trim($timeOnly);
                        
                        if (!empty($timeOnly)) {
                            // Try common time formats (in the target timezone)
                            $timeFormats = [
                                'g:i A',      // 6:00 AM
                                'G:i',        // 6:00
                                'H:i',        // 06:00
                            ];
                            
                            $parsedTime = null;
                            foreach ($timeFormats as $format) {
                                $tm = DateTime::createFromFormat($format, $timeOnly, new DateTimeZone($targetTimezone));
                                if ($tm !== false) {
                                    $parsedTime = $tm;
                                    break;
                                }
                            }
                            
                            // If time parsing failed, try combining date and time string
                            if (!$parsedTime) {
                                $dateTimeString = $datePart . ' ' . $timeOnly;
                                $timestamp = strtotime($dateTimeString . ' ' . $targetTimezone);
                                if ($timestamp !== false) {
                                    $parsedTime = new DateTime('@' . $timestamp);
                                    $parsedTime->setTimezone(new DateTimeZone($targetTimezone));
                                }
                            }
                            
                            // Combine date and time (in the correct timezone)
                            if ($parsedTime) {
                                $parsedDate->setTime(
                                    (int)$parsedTime->format('H'),
                                    (int)$parsedTime->format('i'),
                                    (int)$parsedTime->format('s')
                                );
                            }
                        }
                    }
                    
                    // Convert to UTC for storage (ISO 8601 format)
                    if ($parsedDate) {
                        $parsedDate->setTimezone(new DateTimeZone('UTC'));
                        $result['startDate'] = $parsedDate->format(DATE_ATOM);
                    }
                } catch (Exception $e) {
                    // If parsing fails, return null
                }
            }
        }
    }

    return array_filter($result, static fn($value) => !empty($value));
}

// ✅ NEW: Extract location from HTML (fallback if structured data not available)
function extractLocationFromHtml(string $html): array
{
    $result = [
        'city' => null,
        'state' => null,
        'venue' => null,
    ];

    // Extract location from <div class="location"> (within date-time-location section)
    // Pattern: <div class="location" data-v-...>Atherton, CA</div>
    // More specific pattern to avoid matching date-time-location container
    if (preg_match('/<div[^>]*class=["\'][^"\']*date-time-location[^"\']*["\'][^>]*>.*?<div[^>]*class=["\'][^"\']*location[^"\']*["\'][^>]*>(.*?)<\/div>/is', $html, $locationMatch) ||
        preg_match('/<div[^>]*class=["\'][^"\']*location[^"\']*["\'][^>]*data-v[^>]*>(.*?)<\/div>/is', $html, $locationMatch)) {
        $locationText = $locationMatch[1];
        // Remove HTML comments and tags
        $locationText = preg_replace('/<!--.*?-->/s', '', $locationText);
        $locationText = strip_tags($locationText);
        $locationText = preg_replace('/\s+/', ' ', trim($locationText));
        
        if (!empty($locationText)) {
            // Parse location string like "Atherton, CA" or "City, State"
            // Try to split by comma
            $parts = preg_split('/\s*,\s*/', $locationText, 2);
            
            if (count($parts) >= 2) {
                // Format: "City, State"
                $result['city'] = trim($parts[0]);
                $result['state'] = trim($parts[1]);
            } elseif (count($parts) === 1) {
                // Single part - could be city or state
                $singlePart = trim($parts[0]);
                // If it's a 2-letter uppercase (likely state abbreviation)
                if (preg_match('/^[A-Z]{2}$/', $singlePart)) {
                    $result['state'] = $singlePart;
                } else {
                    // Otherwise assume it's a city
                    $result['city'] = $singlePart;
                }
            }
        }
    }

    return array_filter($result, static fn($value) => !empty($value));
}

function sanitizeDetail(array $data): array
{
    $detail = [
        'title' => $data['name'] ?? null,
        'description' => $data['description'] ?? null,
        'startDate' => $data['startDate'] ?? null,
        'image' => normalizeImage($data['image'] ?? null),
    ];

    if (isset($data['location']) && is_array($data['location'])) {
        $detail['venue'] = $data['location']['name'] ?? null;
        if (isset($data['location']['address']) && is_array($data['location']['address'])) {
            $address = $data['location']['address'];
            $detail['city'] = $address['addressLocality'] ?? null;
            $detail['state'] = $address['addressRegion'] ?? null;
        }
    }

    foreach (['homeTeam' => 'home_team', 'awayTeam' => 'away_team'] as $source => $target) {
        if (isset($data[$source])) {
            if (is_array($data[$source])) {
                // Extract team name
                if (isset($data[$source]['name'])) {
                $detail[$target] = $data[$source]['name'];
                }
                // ✅ NEW: Extract team logo (official logo from structured data)
                if (isset($data[$source]['logo'])) {
                    $logoKey = $source === 'homeTeam' ? 'home_logo' : 'away_logo';
                    $detail[$logoKey] = normalizeImage($data[$source]['logo']);
                }
            } elseif (is_string($data[$source])) {
                $detail[$target] = $data[$source];
            }
        }
    }

    $clean = array_filter(
        $detail,
        static fn($value) => !is_null($value) && $value !== ''
    );

    return $clean;
}

function normalizeImage($image): ?string
{
    if (is_string($image)) {
        return $image;
    }
    if (is_array($image) && isset($image[0]) && is_string($image[0])) {
        return $image[0];
    }
    return null;
}

function extract_meta_content(string $html, string $property): ?string
{
    $propertyPattern = preg_quote($property, '/');
    if (preg_match('/<meta[^>]+property=["\']' . $propertyPattern . '["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $matches)) {
        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
    }
    if (preg_match('/<meta[^>]+name=["\']' . $propertyPattern . '["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $matches)) {
        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
    }
    return null;
}

