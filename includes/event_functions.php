<?php
declare(strict_types=1);

/**
 * Core event fetching logic extracted from api/event.php
 * Allows direct function calls to bypass local CURL issues.
 */

require_once __DIR__ . '/cache.php';

/**
 * Get event data by ID (handles search and direct scraping)
 */
function get_event_data_internal(string $eventId): ?array
{
    if ($eventId === '') {
        return null;
    }

    // Allow forward slash for event IDs like "school-name/gam123"
    $eventId = preg_replace('/[^a-zA-Z0-9_\/-]/', '', $eventId);

    // Generate minimal variations
    $eventIdVariations = [$eventId];
    if (strpos($eventId, '/') !== false) {
        $segments = explode('/', $eventId);
        $lastSegment = end($segments);
        if ($lastSegment !== $eventId && !empty($lastSegment)) {
            $eventIdVariations[] = $lastSegment;
        }
        if (preg_match('/^gam[a-z0-9]+$/i', $lastSegment)) {
            $gameIdWithE = preg_replace('/^gam/i', 'game', $lastSegment);
            if ($gameIdWithE !== $lastSegment) $eventIdVariations[] = $gameIdWithE;
        } elseif (preg_match('/^game[a-z0-9]+$/i', $lastSegment)) {
            $gameIdWithoutE = preg_replace('/^game/i', 'gam', $lastSegment);
            if ($gameIdWithoutE !== $lastSegment) $eventIdVariations[] = $gameIdWithoutE;
        }
    }

    $cacheKey = 'event_internal_' . md5($eventId);
    
    // Check short cache (30s) for live/upcoming
    $cached = cache_get($cacheKey, 30);
    if ($cached !== null && isset($cached['data'])) {
        $status = strtolower($cached['data']['item']['status'] ?? '');
        if (in_array($status, ['live', 'upcoming'])) {
            return $cached['data'];
        }
    }

    // Check long cache (10m) for others
    $cached = cache_get($cacheKey, 600);
    if ($cached !== null && isset($cached['data'])) {
        $status = strtolower($cached['data']['item']['status'] ?? '');
        if (!in_array($status, ['live', 'upcoming'])) {
            return $cached['data'];
        }
    }

    $statuses = ['live', 'upcoming'];
    $eventIdVariations = array_slice($eventIdVariations, 0, 3);

    foreach ($eventIdVariations as $idVariation) {
        foreach (['key', 'id'] as $param) {
            foreach ($statuses as $status) {
                $url = "https://search-api.nfhsnetwork.com/v3/search/events/{$status}?" . urlencode($param) . "=" . urlencode($idVariation) . "&start=0&size=1&card=true";
                $payload = fetchJson_internal($url);
                
                if ($payload && !empty($payload['items'])) {
                    $item = $payload['items'][0];
                    $itemSiteUrl = $item['site_url'] ?? '';
                    if (!empty($itemSiteUrl) && (strpos($itemSiteUrl, $eventId) !== false || strpos($itemSiteUrl, $idVariation) !== false)) {
                        $details = fetchEventDetails_internal($itemSiteUrl);
                        $result = ['source' => $url, 'item' => $item, 'details' => $details];
                        $isLive = in_array(strtolower($item['status'] ?? ''), ['live', 'upcoming']);
                        cache_set($cacheKey, $result, $isLive ? 30 : 600);
                        return $result;
                    }
                }
            }
        }
    }

    // Fallback: Direct Scraping
    $directUrls = [];
    if (strpos($eventId, '/') !== false) {
        $segments = explode('/', $eventId);
        $lastSegment = end($segments);
        $firstSegment = $segments[0] ?? '';
        $directUrls[] = "https://www.nfhsnetwork.com/events/{$eventId}";
        if (!empty($lastSegment)) {
            $directUrls[] = "https://www.nfhsnetwork.com/events/{$lastSegment}";
            if (preg_match('/^gam[a-z0-9]+$/i', $lastSegment)) {
                $directUrls[] = "https://www.nfhsnetwork.com/events/" . preg_replace('/^gam/i', 'game', $lastSegment);
            }
        }
    } else {
        $directUrls[] = "https://www.nfhsnetwork.com/events/{$eventId}";
    }

    $directUrls = array_values(array_unique(array_slice($directUrls, 0, 3)));
    foreach ($directUrls as $directUrl) {
        $html = fetchContent_internal($directUrl, ['Accept: text/html'], 1);
        if ($html === null || strlen($html) < 100) continue;

        $details = fetchEventDetails_internal($directUrl);
        if (!empty($details['title']) || !empty($details['description']) || !empty($details['image'])) {
            $item = [
                'site_url' => $directUrl,
                'key' => $eventId,
                'title' => $details['title'] ?? '',
                'description' => $details['description'] ?? '',
                'background_image' => $details['image'] ?? '',
                'first_title' => $details['away_team'] ?? '',
                'second_title' => $details['home_team'] ?? '',
                'first_logo' => $details['away_logo'] ?? null,
                'second_logo' => $details['home_logo'] ?? null,
                'city' => $details['city'] ?? '',
                'state' => $details['state'] ?? '',
                'venue' => $details['venue'] ?? '',
                'startDate' => $details['startDate'] ?? '',
            ];
            $result = ['source' => $directUrl, 'method' => 'direct_scrape', 'item' => $item, 'details' => $details];
            cache_set($cacheKey, $result, 600);
            return $result;
        }
    }

    return null;
}

/**
 * Internal helper: fetchJson
 */
function fetchJson_internal(string $url): ?array
{
    $cacheKey = get_cache_key($url);
    $cached = cache_get($cacheKey, 30);
    if ($cached !== null && isset($cached['data'])) {
        return $cached['data'];
    }
    
    $response = fetchContent_internal($url, ['Accept: application/json'], 2);
    if ($response === null) return null;
    
    $data = json_decode($response, true);
    if (!is_array($data)) return null;
    
    cache_set($cacheKey, $data, 30);
    return $data;
}

/**
 * Internal helper: fetchContent
 */
function fetchContent_internal(string $url, array $headers = [], int $retries = 1): ?string
{
    for ($attempt = 0; $attempt <= $retries; $attempt++) {
        if ($attempt > 0) usleep(200000);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array_merge(
                [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept: text/html,application/json;q=0.9,*/*;q=0.8',
                ],
                $headers
            ),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (in_array($httpCode, [503, 502, 504])) return null;
        if ($response === false || !empty($curlError)) {
            if ($attempt < $retries) continue;
            return null;
        }
        if ($httpCode >= 400) return null;

        return $response;
    }
    return null;
}

/**
 * Internal helper: fetchEventDetails
 */
function fetchEventDetails_internal(string $url): array
{
    if ($url === '' || stripos($url, 'nfhsnetwork.com') === false) return [];

    $cacheKey = 'event_details_int_' . md5($url);
    $cached = cache_get($cacheKey, 1800);
    if ($cached !== null && isset($cached['data'])) return $cached['data'];

    $html = fetchContent_internal($url, ['Accept: text/html']);
    if ($html === null) return [];

    $ogMeta = [
        'title' => extract_meta_content_internal($html, 'og:title'),
        'description' => extract_meta_content_internal($html, 'og:description'),
        'image' => extract_meta_content_internal($html, 'og:image'),
    ];

    $detail = [];
    if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
        foreach ($matches[1] as $block) {
            $decoded = json_decode(trim($block), true);
            if (!is_array($decoded)) continue;
            $candidates = isset($decoded[0]) ? $decoded : [$decoded];
            foreach ($candidates as $candidate) {
                if (!is_array($candidate)) continue;
                $type = $candidate['@type'] ?? '';
                if ($type !== 'Event' && $type !== 'SportsEvent') continue;
                $detail = sanitizeDetail_internal($candidate);
                foreach ($ogMeta as $key => $value) {
                    if ($value && empty($detail[$key])) $detail[$key] = $value;
                }
                break 2;
            }
        }
    }

    if (empty($detail['home_team'])) {
        $teamsData = extractTeamsFromHtml_internal($html);
        foreach ($teamsData as $k => $v) if (empty($detail[$k])) $detail[$k] = $v;
    }

    if (empty($detail['startDate'])) {
        $dateTimeData = extractDateTimeFromHtml_internal($html);
        if (!empty($dateTimeData['startDate'])) $detail['startDate'] = $dateTimeData['startDate'];
    }

    if (empty($detail['city'])) {
        $locationData = extractLocationFromHtml_internal($html);
        foreach ($locationData as $k => $v) if (empty($detail[$k])) $detail[$k] = $v;
    }

    $result = !empty($detail) ? array_merge($ogMeta, $detail) : $ogMeta;
    $result = array_filter($result, static fn($v) => !empty($v));

    if (!empty($result['title']) || !empty($result['description'])) {
        cache_set($cacheKey, $result, 1800);
        return $result;
    }
    
    cache_set($cacheKey, [], 300);
    return [];
}

/**
 * Helpers from api/event.php
 */
function sanitizeDetail_internal(array $data): array {
    $detail = [
        'title' => $data['name'] ?? null,
        'description' => $data['description'] ?? null,
        'startDate' => $data['startDate'] ?? null,
        'image' => normalizeImage_internal($data['image'] ?? null),
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
                if (isset($data[$source]['name'])) $detail[$target] = $data[$source]['name'];
                if (isset($data[$source]['logo'])) {
                    $logoKey = $source === 'homeTeam' ? 'home_logo' : 'away_logo';
                    $detail[$logoKey] = normalizeImage_internal($data[$source]['logo']);
                }
            } elseif (is_string($data[$source])) $detail[$target] = $data[$source];
        }
    }
    return array_filter($detail, fn($v) => !is_null($v) && $v !== '');
}

function normalizeImage_internal($image): ?string {
    if (is_string($image)) return $image;
    if (is_array($image) && isset($image[0]) && is_string($image[0])) return $image[0];
    return null;
}

function extract_meta_content_internal(string $html, string $property): ?string {
    $p = preg_quote($property, '/');
    if (preg_match('/<meta[^>]+(?:property|name)=["\']' . $p . '["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
    }
    return null;
}

function extractTeamsFromHtml_internal(string $html): array {
    // Simplified extraction logic for better maintenance
    $result = [];
    if (preg_match_all('/<div[^>]*data-test=["\']organization-card["\'][^>]*>.*?<p[^>]*data-test=["\']title["\'][^>]*>(.*?)<\/p>/is', $html, $m)) {
        $teams = array_map(fn($v) => trim(strip_tags(preg_replace('/<!--.*?-->/s', '', $v))), $m[1]);
        if (count($teams) >= 2) {
            $result['away_team'] = $teams[0];
            $result['home_team'] = $teams[1];
        }
    }
    return $result;
}

function extractDateTimeFromHtml_internal(string $html): array {
    if (preg_match('/<div[^>]*class=["\'][^"\']*date-time[^"\']*["\'][^>]*>(.*?)<\/div>/is', $html, $m)) {
        $dtStr = trim(strip_tags(preg_replace('/<!--.*?-->/s', '', $m[1])));
        if ($dtStr) {
            $parts = explode('|', $dtStr);
            $ts = strtotime(trim($parts[0] ?? ''));
            if ($ts) return ['startDate' => date(DATE_ATOM, $ts)];
        }
    }
    return [];
}

function extractLocationFromHtml_internal(string $html): array {
    $res = [];
    if (preg_match('/<div[^>]*class=["\'][^"\']*location[^"\']*["\'][^>]*>(.*?)<\/div>/is', $html, $m)) {
        $loc = trim(strip_tags(preg_replace('/<!--.*?-->/s', '', $m[1])));
        $parts = explode(',', $loc);
        if (count($parts) >= 2) {
            $res['city'] = trim($parts[0]);
            $res['state'] = trim($parts[1]);
        }
    }
    return $res;
}
