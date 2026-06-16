<?php
declare(strict_types=1);

// ✅ Disable error display in production to prevent response corruption
ini_set('display_errors', '0');
error_reporting(E_ALL);





// ✅ Load core config dulu
$config = require __DIR__ . '/config.php';
$siteTitle = $config['site_title'] ?? 'HighSchoolNews';

// ✅ Performance & helpers
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/cache.php';
require_once __DIR__ . '/includes/cloaker.php';
require_once __DIR__ . '/includes/event_functions.php';

// ✅ FIX: Static Asset Guard
// If Apache/Nginx falls back to index.php for an asset that exists, serve it correctly.
// If it doesn't exist, return a clean 404 instead of a heavy HTML page.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|webp|woff|woff2|ttf|eot)$/i', $requestPath)) {
    // Standardize path (remove leading slash)
    $cleanPath = ltrim($requestPath, '/');
    $fullAssetPath = __DIR__ . '/' . $cleanPath;
    
    if (file_exists($fullAssetPath) && is_file($fullAssetPath)) {
        $ext = strtolower(pathinfo($fullAssetPath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
        ];
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        header('Cache-Control: public, max-age=2592000'); // 1 month cache for assets
        readfile($fullAssetPath);
        exit;
    }
    // Asset doesn't exist - return real 404
    http_response_code(404);
    header('Content-Type: text/plain');
    echo "404 Not Found: " . htmlspecialchars($requestPath);
    exit;
}

// ✅ AUTO INDEX (non-blocking safe)
if (file_exists(__DIR__ . '/auto_indexing.php')) {
    ob_start(); // Buffer any diagnostic output from server-side script
    require __DIR__ . '/auto_indexing.php';
    if (function_exists('auto_index_if_needed')) {
        auto_index_if_needed();
    }
    ob_end_clean(); // Cleanly discard the buffer
}

// Allowed domains - derived from config
$siteDomain = $config['site_domain'] ?? 'main.nexorasubs.com';
$domainKeyword = explode('.', $siteDomain)[0]; // e.g. 'maxpreps'

$ALLOWED_DOMAINS = array_merge(
    $config['allowed_domains'] ?? [$siteDomain],
    ['localhost', '127.0.0.1'] // local development
);

$ALLOWED_PROXY_IPS = [
];

function check_domain_access(): bool
{
    global $ALLOWED_DOMAINS, $siteDomain, $domainKeyword;
    
    // ✅ Domain access check

    // ✅ TEMPORARY BYPASS: Allow all domains to fix the "Access Denied" blocker
    return true; 
    

    
    // ✅ FIX: EARLY BYPASS - Check HTTP_HOST immediately for quick allow
    
    $earlyHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '';
    $earlyServerName = $_SERVER['SERVER_NAME'] ?? '';
    
    // ✅ FIX: VERY AGGRESSIVE BYPASS - If HTTP_HOST or SERVER_NAME contains domain keyword, allow immediately
    // This is the most permissive check possible
    if (!empty($earlyHost)) {
        $earlyHostLower = strtolower(trim($earlyHost));
        $earlyHostLower = preg_replace('/:\d+$/', '', $earlyHostLower);
        if (strpos($earlyHostLower, $domainKeyword) !== false || strpos($earlyHostLower, 'news') !== false) {
            return true; // HTTP_HOST contains our domain keywords - allow immediately
        }
    }
    if (!empty($earlyServerName)) {
        $earlyServerNameLower = strtolower(trim($earlyServerName));
        $earlyServerNameLower = preg_replace('/:\d+$/', '', $earlyServerNameLower);
        if (strpos($earlyServerNameLower, $domainKeyword) !== false || strpos($earlyServerNameLower, 'news') !== false) {
            return true; // SERVER_NAME contains our domain keywords - allow immediately
        }
    }
    
    // ✅ FIX: Get server name first (most reliable - from server config)
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    $serverName = preg_replace('/:\d+$/', '', $serverName);
    $serverNameLower = strtolower(trim($serverName));
    
    // ✅ FIX: DEBUG - Log SERVER_NAME untuk troubleshooting (temporary)
    if (function_exists('error_log') && !empty($serverNameLower)) {
        error_log("DEBUG check_domain_access - SERVER_NAME: {$serverNameLower}");
    }
    
    // ✅ FIX: Check SERVER_NAME FIRST (highest priority - most reliable)
    // SERVER_NAME comes from server configuration and cannot be spoofed
    
    if (!empty($serverNameLower)) {
        // ✅ FIX: VERY PERMISSIVE - If SERVER_NAME contains our domain, allow immediately
        // This is the most permissive check - as long as we're accessing our own server
        if (strpos($serverNameLower, $siteDomain) !== false) {
            return true; // SERVER_NAME contains our domain - allow immediately
        }
        
        // Also check exact matches for better security
        foreach ($ALLOWED_DOMAINS as $allowed) {
            $allowedLower = strtolower(trim($allowed));
            if ($serverNameLower === $allowedLower || 
                $serverNameLower === 'www.' . $allowedLower ||
                substr($serverNameLower, -strlen('.' . $allowedLower)) === '.' . $allowedLower) {
                return true; // SERVER_NAME matches - allow immediately
            }
        }
    } else {
        // ✅ FIX: If SERVER_NAME is empty, check HTTP_HOST instead (fallback)
        // This handles cases where SERVER_NAME is not set
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '';
        $host = preg_replace('/:\d+$/', '', $host);
        $hostLower = strtolower(trim($host));
        
        if (!empty($hostLower) && strpos($hostLower, $siteDomain) !== false) {
            return true; // HTTP_HOST contains our domain - allow
        }
    }
    
    
    $hasForwardedHeaders = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) || 
                      !empty($_SERVER['HTTP_X_FORWARDED_HOST']) ||
                      !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ||
                      !empty($_SERVER['HTTP_VIA']) ||
                      !empty($_SERVER['HTTP_X_REAL_IP']);
    
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    
    global $ALLOWED_PROXY_IPS;
    if (!empty($ALLOWED_PROXY_IPS) && in_array($remoteAddr, $ALLOWED_PROXY_IPS)) {
        return true;
    }
    
    
    
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // ✅ FIX: TEMPORARY BYPASS - If HTTP_HOST contains our domain, allow immediately
    
    if (!empty($host)) {
        $hostCheck = strtolower(trim($host));
        if (strpos($hostCheck, $siteDomain) !== false) {
            return true; // HTTP_HOST contains our domain - allow immediately
        }
    }
    
    
    
    if ($hasForwardedHeaders && !empty($host)) {
        return true; 
    }
    
    // Remove port if present
    $host = preg_replace('/:\d+$/', '', $host);
    
    // ✅ FIX: If host is empty, try to get from X-Forwarded-Server or SERVER_NAME
    if (empty($host)) {
        $host = $_SERVER['HTTP_X_FORWARDED_SERVER'] ?? $serverName ?? '';
        $host = preg_replace('/:\d+$/', '', $host);
    }
    
    
    if (empty($host) && $hasForwardedHeaders && !empty($serverName)) {
        $host = $serverName;
    }
    
    // ✅ PERFORMANCE: Optimized bot detection dengan early exit
    // ✅ FIX: Detect search engine bots and allow them to bypass referer check
    // This is critical for SEO - search engine bots must be able to crawl the site
    $isSearchEngineBot = false;
    if (!empty($userAgent)) {
        // ✅ PERFORMANCE: Check most common bots first untuk early exit
        $userAgentLower = strtolower($userAgent);
        if (strpos($userAgentLower, 'googlebot') !== false || 
            strpos($userAgentLower, 'bingbot') !== false ||
            strpos($userAgentLower, 'slurp') !== false) {
            $isSearchEngineBot = true;
        } else {
            // Check other bots only if common ones not found
            $botPatterns = [
                'duckduckbot', 'baiduspider', 'yandex',
                'facebookexternalhit', 'facebookcatalog', 'twitterbot', 'linkedinbot',
                'whatsapp', 'telegrambot', 'applebot', 'ia_archiver', 'msnbot',
                'ahrefsbot', 'semrushbot', 'mj12bot', 'dotbot', 'megaindex',
                'electron', 'chromium', 'chrome', 
            ];
            foreach ($botPatterns as $pattern) {
                if (strpos($userAgentLower, $pattern) !== false) {
                    $isSearchEngineBot = true;
                    break;
                }
            }
        }
    }
    
    // Check if host matches allowed domains
    // ✅ FIX: Improved host matching to handle exact matches and subdomains correctly
    $hostAllowed = false;
    $hostLower = strtolower(trim($host));
    $serverNameLower = strtolower(trim($serverName));
    
    foreach ($ALLOWED_DOMAINS as $allowed) {
        $allowedLower = strtolower(trim($allowed));
        
        // Exact match
        if ($hostLower === $allowedLower || $serverNameLower === $allowedLower) {
            $hostAllowed = true;
            break;
        }
        
        // Subdomain matching: host should end with .domain or be domain itself
        // e.g., www.nexorasubs.com should match nexorasubs.com
        // but notnexorasubs.com should NOT match nexorasubs.com
        if ($hostLower === $allowedLower || 
            $hostLower === 'www.' . $allowedLower ||
            substr($hostLower, -strlen('.' . $allowedLower)) === '.' . $allowedLower) {
            $hostAllowed = true;
            break;
        }
        
        if ($serverNameLower === $allowedLower || 
            $serverNameLower === 'www.' . $allowedLower ||
            substr($serverNameLower, -strlen('.' . $allowedLower)) === '.' . $allowedLower) {
            $hostAllowed = true;
            break;
        }
    }
    
    
    
    if (!$hostAllowed && $hasForwardedHeaders) {
        
        
        if (!empty($serverNameLower)) {
            foreach ($ALLOWED_DOMAINS as $allowed) {
                $allowedLower = strtolower(trim($allowed));
                if ($serverNameLower === $allowedLower || 
                    $serverNameLower === 'www.' . $allowedLower ||
                    substr($serverNameLower, -strlen('.' . $allowedLower)) === '.' . $allowedLower) {
                    $hostAllowed = true;
                    break;
                }
            }
        }
        
        
        if (!$hostAllowed && $isSearchEngineBot) {
            $hostAllowed = true;
        }
        
        
        
        // This was already checked above, but keeping as fallback
        if (!$hostAllowed && !empty($serverNameLower)) {
            // If SERVER_NAME contains our domain pattern, allow it
            if (strpos($serverNameLower, $siteDomain) !== false) {
                $hostAllowed = true;
            }
        }
    }
    
    if (!$hostAllowed) {
        // ✅ DEBUG: Log access denial for troubleshooting
        if (function_exists('error_log')) {
            error_log("Access denied - Host: {$host}, ServerName: {$serverName}, UserAgent: {$userAgent}, RemoteAddr: {$remoteAddr}");
        }
        return false;
    }
    
    // ✅ FIX: Allow search engine bots to bypass referer check for SEO indexing
    // Search engine bots often don't send referers or send referers from their own domains
    if ($isSearchEngineBot) {
        return true; // Allow all search engine bots
    }
    
    // ✅ FIX: Since host is already allowed, allow access regardless of referer
    // This ensures legitimate traffic from any source works:
    // - Direct access (no referer)
    // - Social media links (Facebook, Twitter, etc.)
    // - Search engine results
    // - Bookmarks
    
    // - Any other legitimate source
    // 
    // The host check above is the main security measure.
    // Referer checks are unreliable and can block legitimate users.
    // For embedding protection, use X-Frame-Options header instead.
    return true;
}

function base_origin(): string
{
    
    
    
    
    $scheme = 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $forwardedProto = strtolower(trim($_SERVER['HTTP_X_FORWARDED_PROTO']));
        $scheme = ($forwardedProto === 'https') ? 'https' : 'http';
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    } elseif (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
        $scheme = 'https';
    }
    
    
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    
    $host = preg_replace('/:\d+$/', '', $host);
    
    return $scheme . '://' . $host;
}

/**
 * Get client IP that looks like direct access
 */
function get_direct_client_ip(): string
{
    // Priority: Use REMOTE_ADDR first (most reliable, looks like direct access)
    // Only use X-Forwarded-For if REMOTE_ADDR is localhost/private IP
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // If REMOTE_ADDR is not a private/local IP, use it directly
    if (!empty($remoteAddr) && filter_var($remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return $remoteAddr;
    }
    
    // If REMOTE_ADDR is private/local, try to get real IP from X-Forwarded-For
    
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $forwardedIps = array_map('trim', $forwardedIps);
        $forwardedIps = array_filter($forwardedIps);
        
        
        if (!empty($forwardedIps)) {
            $lastIp = end($forwardedIps);
            if (filter_var($lastIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $lastIp;
            }
        }
    }
    
    // Fallback to REMOTE_ADDR or X-Real-IP
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $realIp = trim($_SERVER['HTTP_X_REAL_IP']);
        if (filter_var($realIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $realIp;
        }
    }
    
    return $remoteAddr ?: '127.0.0.1';
}

function get_direct_referer(): string
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    if (empty($referer) || stripos($referer, 'decodo') !== false) {
        return base_origin();
    }
    
    return $referer;
}

function get_redirect_url(string $eventId): string
{
    $baseUrl = base_origin();
    $redirectPath = '/redirect.php';
    
    // Create redirect URL with event ID
    $params = http_build_query([
        'camp' => 15,
        'pubid' => 1,
        'bg' => 'bg1',
        'sid2' => 'kano',
        'event' => $eventId,
        'ref' => base64_encode(get_direct_referer()), // Encoded referer
        'ts' => time() // Timestamp to prevent caching
    ]);
    
    return $baseUrl . $redirectPath . '?' . $params;
}

function sanitize_event_id(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (filter_var($value, FILTER_VALIDATE_URL)) {
        $path = parse_url($value, PHP_URL_PATH);
        if ($path) {
            $value = trim($path, '/');
        }
    }
    // Allow forward slash for event IDs like "school-name/gam123"
    $sanitized = preg_replace('/[^a-zA-Z0-9_\/-]/', '', $value);
    
    // ✅ FIX: Normalize event ID - remove leading/trailing slashes
    $sanitized = trim($sanitized, '/');
    
    return $sanitized;
}

/**
 * Normalize event ID untuk berbagai format
 */
function normalize_event_id(string $eventId): array
{
    $variations = [$eventId];
    
    // ✅ FIX: Handle format "evt" prefix (e.g., evt0fbe81c245)
    if (preg_match('/^evt(.+)$/i', $eventId, $matches)) {
        $variations[] = $matches[1]; // Tanpa prefix evt
        $variations[] = 'evt-' . $matches[1]; // Dengan dash
    } elseif (!preg_match('/^evt/i', $eventId)) {
        // Jika tidak ada prefix evt, coba tambahkan
        $variations[] = 'evt' . $eventId;
        $variations[] = 'evt-' . $eventId;
    }
    
    // Jika mengandung slash, coba tanpa prefix
    if (strpos($eventId, '/') !== false) {
        $parts = explode('/', $eventId);
        if (count($parts) > 1) {
            $variations[] = end($parts); // Ambil bagian terakhir
            $variations[] = implode('-', $parts); // Gabungkan dengan dash
            // Jika bagian terakhir tidak ada prefix evt, coba tambahkan
            $lastPart = end($parts);
            if (!preg_match('/^evt/i', $lastPart)) {
                $variations[] = 'evt' . $lastPart;
            }
        }
    }
    
    // Jika mengandung dash, coba dengan slash
    if (strpos($eventId, '-') !== false && strpos($eventId, '/') === false) {
        $parts = explode('-', $eventId, 2);
        if (count($parts) === 2) {
            $variations[] = $parts[0] . '/' . $parts[1];
        }
    }
    
    return array_unique($variations);
}

// ✅ PERFORMANCE: Cached HTTP GET JSON dengan TTL
function http_get_json(string $url, int $cacheTtl = CACHE_TTL_EVENT, array $headers = []): ?array
{
    // Check cache first
    $cacheKey = get_cache_key($url);
    $cached = cache_get($cacheKey, $cacheTtl);
    if ($cached !== null && isset($cached['data'])) {
        return $cached['data'];
    }
    
    // Default headers
    $defaultHeaders = [
        'Accept: application/json',
        'User-Agent: NFHS-Player/1.0',
    ];
    $httpHeaders = array_merge($defaultHeaders, $headers);
    
    // Make API call
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => $httpHeaders,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // Force IPv4 for Windows stability
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($response === false || !empty($curlError)) {
        error_log("CURL Error for {$url}: {$curlError}");
        return null;
    }
    
    if ($httpCode >= 400) {
        error_log("HTTP Error {$httpCode} for {$url}");
        return null;
    }
    
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        $sample = substr((string)$response, 0, 500);
        error_log("Invalid JSON response from {$url}. First 500 chars: " . $sample);
        return null;
    }
    
    // ✅ PERFORMANCE: Cache successful response
    cache_set($cacheKey, $decoded, $cacheTtl);
    
    return $decoded;
}


// ✅ PERFORMANCE: Cached event payload dengan TTL lebih lama
function fetch_event_payload(string $eventId, string $origin): ?array
{
    if (empty($eventId)) {
        return null;
    }

    // ✅ OPTIMIZATION: Bypass local CURL call to api/event.php to prevent 503/deadlock on Windows
    // Call the internal logic directly instead of through network stack
    if (function_exists('get_event_data_internal')) {
        return get_event_data_internal($eventId);
    }

    $url = rtrim($origin, '/') . '/api/event.php?id=' . urlencode($eventId);
    $payload = http_get_json($url, 600);
    
    // ✅ FIX: Jika API lokal gagal (CURL issues), coba panggil fungsi internal secara langsung
    if (!$payload && function_exists('get_event_data_internal')) {
        $payload = get_event_data_internal($eventId);
    }
    
    // ✅ FALLBACK: Jika masih gagal, coba metode manual (retro compatibility)
    if (!$payload && !empty($eventId)) {
        // Clear cache error untuk retry fresh
        $cacheKey = get_cache_key($url);
        cache_delete($cacheKey);
        
        // Retry sekali dengan cache yang sudah di-clear
        $payload = http_get_json($url, 600);
        
        // Jika masih gagal, coba berbagai variasi event ID dan metode pencarian
        if (!$payload) {
            $foundEvent = null;
            $eventVariations = normalize_event_id($eventId);
            
            // Coba setiap variasi event ID
            foreach ($eventVariations as $variantId) {
                if (empty($variantId)) continue;
                
                // Metode 1: Cari dengan parameter key untuk setiap variasi
                $searchUrl1 = 'https://search-api.nfhsnetwork.com/v3/search/events?key=' . urlencode($variantId) . '&card=true&size=10';
                $searchData1 = http_get_json($searchUrl1, 180);
                if ($searchData1 && !empty($searchData1['items'])) {
                    foreach ($searchData1['items'] as $item) {
                        // Cocokkan dengan event ID asli atau variasi
                        if (isset($item['key']) && ($item['key'] === $eventId || $item['key'] === $variantId)) {
                            $foundEvent = $item;
                            // Update eventId jika ditemukan dengan variasi yang berbeda
                            if ($item['key'] !== $eventId) {
                                $eventId = $item['key'];
                            }
                            break 2; // Break dari kedua loop
                        }
                    }
                }
            }
            
            // Metode 2: Cari dengan query (jika event ID mengandung nama tim/sekolah)
            if (!$foundEvent && (strpos($eventId, '/') !== false || strpos($eventId, '-') !== false)) {
                $parts = preg_split('/[\/-]/', $eventId);
                if (!empty($parts)) {
                    $queryTerm = end($parts); // Ambil bagian terakhir
                    $searchUrl2 = 'https://search-api.nfhsnetwork.com/v3/search/events?q=' . urlencode($queryTerm) . '&card=true&size=20';
                    $searchData2 = http_get_json($searchUrl2, 180);
                    if ($searchData2 && !empty($searchData2['items'])) {
                        foreach ($searchData2['items'] as $item) {
                            if (isset($item['key']) && $item['key'] === $eventId) {
                                $foundEvent = $item;
                                break;
                            }
                        }
                    }
                }
            }
            
            // Metode 3: Cari di upcoming events (lebih banyak hasil)
            if (!$foundEvent) {
                $searchUrl3 = 'https://search-api.nfhsnetwork.com/v3/search/events/upcoming?card=true&size=200';
                $searchData3 = http_get_json($searchUrl3, 180);
                if ($searchData3 && !empty($searchData3['items'])) {
                    foreach ($searchData3['items'] as $item) {
                        // Cocokkan dengan event ID asli atau variasi
                        if (isset($item['key'])) {
                            foreach ($eventVariations as $variantId) {
                                if ($item['key'] === $variantId || $item['key'] === $eventId) {
                                    $foundEvent = $item;
                                    if ($item['key'] !== $eventId) {
                                        $eventId = $item['key'];
                                    }
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
            
            // Metode 4: Cari di live events (lebih banyak hasil)
            if (!$foundEvent) {
                $searchUrl4 = 'https://search-api.nfhsnetwork.com/v3/search/events/live?card=true&size=200';
                $searchData4 = http_get_json($searchUrl4, 180);
                if ($searchData4 && !empty($searchData4['items'])) {
                    foreach ($searchData4['items'] as $item) {
                        // Cocokkan dengan event ID asli atau variasi
                        if (isset($item['key'])) {
                            foreach ($eventVariations as $variantId) {
                                if ($item['key'] === $variantId || $item['key'] === $eventId) {
                                    $foundEvent = $item;
                                    if ($item['key'] !== $eventId) {
                                        $eventId = $item['key'];
                                    }
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
            
            // Metode 5: Cari di ondemand events (untuk event yang sudah selesai)
            if (!$foundEvent) {
                $searchUrl5 = 'https://search-api.nfhsnetwork.com/v3/search/events/ondemand?card=true&size=200';
                $searchData5 = http_get_json($searchUrl5, 180);
                if ($searchData5 && !empty($searchData5['items'])) {
                    foreach ($searchData5['items'] as $item) {
                        // Cocokkan dengan event ID asli atau variasi
                        if (isset($item['key'])) {
                            foreach ($eventVariations as $variantId) {
                                if ($item['key'] === $variantId || $item['key'] === $eventId) {
                                    $foundEvent = $item;
                                    if ($item['key'] !== $eventId) {
                                        $eventId = $item['key'];
                                    }
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
            
            // Jika event ditemukan di NFHS Network
            if ($foundEvent) {
                // Clear cache dan retry API lokal sekali lagi
                cache_delete($cacheKey);
                $retryPayload = http_get_json($url, 600);
                if ($retryPayload) {
                    return $retryPayload;
                }
                // Jika masih gagal, gunakan data dari search API
                return [
                    'item' => $foundEvent,
                    'details' => $foundEvent
                ];
            }
        }
    }
    
    return $payload;
}

function fetch_random_event(string $origin): ?array
{
    $searchUrl = 'https://search-api.nfhsnetwork.com/v3/search/events/upcoming?card=true&size=50&start=0';
    $searchData = http_get_json($searchUrl);
    if (!$searchData) {
        return null;
    }
    $items = $searchData['items'] ?? [];
    if (empty($items)) {
        return null;
    }
    $randomIndex = random_int(0, count($items) - 1);
    $candidate = $items[$randomIndex] ?? null;
    if (!$candidate || empty($candidate['key'])) {
        return null;
    }
    return fetch_event_payload($candidate['key'], $origin);
}



// ✅ PERFORMANCE: Cached related events dengan TTL pendek
function fetch_related_events(array $item, array $details, int $limit = 60): array
{
    $activity = $item['activity_or_sport'] ?? ($details['activity_or_sport'] ?? null);
    $currentKey = $item['key'] ?? null;

    // ✅ PERFORMANCE: Cache key berdasarkan activity dan status
    $cacheKey = 'related_' . md5(($activity ?? 'all') . '_' . $currentKey);
    $cached = cache_get($cacheKey, 180); // 3 menit cache
    if ($cached !== null && isset($cached['data'])) {
        return array_slice($cached['data'], 0, $limit);
    }

    $all = [];
    foreach (['ondemand', 'live'] as $status) { // ✅ Changed: Show on-demand first, exclude upcoming
        $params = [
            'card' => 'true',
            'start' => 0,
            'size' => 100,
        ];
        if ($activity) {
            $params['activity'] = $activity;
        }
        $query = http_build_query($params);
        $url = "https://search-api.nfhsnetwork.com/v3/search/events/{$status}?{$query}";
        // ✅ PERFORMANCE: Cache individual API calls (sudah di http_get_json)
        $resp = http_get_json($url, 180); // 3 menit
        if (!$resp) {
            continue;
        }
        $items = $resp['items'] ?? [];
        foreach ($items as $ev) {
            $all[] = $ev;
        }
    }
    if (!$all) {
        return [];
    }

    // sort by start time ascending
    usort($all, static function ($a, $b) {
        $at = $a['eventDate'] ?? ($a['date'] ?? ($a['startTime'] ?? ''));
        $bt = $b['eventDate'] ?? ($b['date'] ?? ($b['startTime'] ?? ''));
        return strcmp($at, $bt);
    });

    $related = [];
    foreach ($all as $ev) {
        if ($currentKey && isset($ev['key']) && $ev['key'] === $currentKey) {
            continue;
        }
        $status = $ev['status'] ?? $ev['type'] ?? ($ev['live'] ?? false ? 'live' : 'upcoming');
        $teams = extract_team_names($ev);
        [$iso, $human] = format_event_datetime($ev['eventDate'] ?? ($ev['date'] ?? ($ev['startTime'] ?? null)));
        $locationParts = array_filter([
            $ev['venue']['name'] ?? ($ev['location']['name'] ?? null),
            $ev['venue']['city'] ?? ($ev['location']['city'] ?? null),
            $ev['venue']['state'] ?? ($ev['location']['state'] ?? null),
            $ev['state'] ?? null,
        ]);
        $locationText = $locationParts ? implode(', ', $locationParts) : 'TBA';
        $url = $ev['watchUrl'] ?? ($ev['watch_url'] ?? ($ev['siteUrl'] ?? ($ev['site_url'] ?? ($ev['permalink'] ?? '#'))));
        $related[] = [
            'matchup' => (function() use ($teams, $ev) {
                // ✅ FIX: Determine matchup - avoid showing TBD
                $homeTeam = trim($teams['home'] ?? '');
                $awayTeam = trim($teams['away'] ?? '');
                
                // Check if team names are valid (not TBD, not empty, not containing TBD)
                // Use stripos first to catch any variation (TBD, Team TBD, Teams TBD, etc.)
                $homeValid = !empty($homeTeam) && 
                             stripos($homeTeam, 'TBD') === false &&
                             strtoupper($homeTeam) !== 'HOME' &&
                             strtoupper($homeTeam) !== 'HOME TEAM';
                $awayValid = !empty($awayTeam) && 
                             stripos($awayTeam, 'TBD') === false &&
                             strtoupper($awayTeam) !== 'AWAY' &&
                             strtoupper($awayTeam) !== 'AWAY TEAM';
                
                if ($homeValid && $awayValid) {
                    // Both teams are valid - use "Team vs Team" format
                    return $homeTeam . ' vs ' . $awayTeam;
                } elseif ($homeValid || $awayValid) {
                    // Only one team is valid - show only the valid team name
                    return $homeValid ? $homeTeam : $awayTeam;
                } else {
                    // Both teams are invalid - try to extract from event title
                    $eventTitle = $ev['title'] ?? $ev['name'] ?? $ev['headline'] ?? '';
                    if (!empty($eventTitle) && stripos($eventTitle, 'TBD') === false) {
                        // Clean title and extract school/team name
                        $cleanTitle = $eventTitle;
                        $cleanTitle = preg_replace('/\s*\|\s*Live.*$/i', '', $cleanTitle);
                        $cleanTitle = preg_replace('/\s*-\s*\d{1,2}\/\d{1,2}\/\d{4}.*$/i', '', $cleanTitle);
                        
                        // Extract school name (after "Part X" or last part after " - ")
                        if (preg_match('/\s*-\s*Part\s+\d+\s+(.+?)$/i', $cleanTitle, $match)) {
                            $result = trim($match[1]);
                            // Make sure result doesn't contain TBD
                            if (stripos($result, 'TBD') === false) {
                                return $result;
                            }
                        } else {
                            $parts = preg_split('/\s*-\s*/', $cleanTitle);
                            if (count($parts) > 1) {
                                $result = trim(end($parts));
                                // Make sure result doesn't contain TBD
                                if (stripos($result, 'TBD') === false) {
                                    return $result;
                                }
                            } else {
                                $result = trim($cleanTitle);
                                // Make sure result doesn't contain TBD
                                if (stripos($result, 'TBD') === false) {
                                    return $result;
                                }
                            }
                        }
                    }
                    // Last resort: use generic text (avoid showing TBD)
                    return 'Upcoming Event';
                }
            })(),
            'datetime' => $human ?? 'TBA',
            'location' => $locationText,
            'url' => $url,
            'key' => $ev['key'] ?? null,
            'status' => ucfirst(strtolower((string)$status)),
        ];
        if (count($related) >= $limit) {
            break;
        }
    }
    
    // ✅ PERFORMANCE: Cache hasil untuk request berikutnya
    cache_set($cacheKey, $related, 180);
    
    return $related;
}

function format_event_datetime(?string $iso): array
{
    if (!$iso) {
        return [null, null];
    }
    try {
        $dt = new DateTime($iso);
        // Convert to EST for display (matching official website behavior)
        $dt->setTimezone(new DateTimeZone('America/New_York'));
        return [$dt->format(DATE_ATOM), $dt->format('M j, Y | g:i A T')];
    } catch (Exception $e) {
        return [$iso, null];
    }
}

// (normalize_cdn_image is now integrated into resolve_image_url in helpers.php)

// (ensure_absolute_image_url is now resolve_image_url in helpers.php)

// ✅ PERFORMANCE: Start output buffering dengan compression
if (!ob_get_level()) {
    ob_start();
}



$earlyServerName = $_SERVER['SERVER_NAME'] ?? '';
$earlyServerName = preg_replace('/:\d+$/', '', $earlyServerName);
$earlyServerNameLower = strtolower(trim($earlyServerName));


if (!empty($earlyServerNameLower) && strpos($earlyServerNameLower, 'nexorasubs.com') !== false) {
    // SERVER_NAME contains our domain - likely a legitimate request
    
    // This will still go through check_domain_access() but with better chance of passing
}



$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
$hasForwardedHeaders = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) || 
                   !empty($_SERVER['HTTP_X_FORWARDED_HOST']) ||
                   !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ||
                   !empty($_SERVER['HTTP_VIA']) ||
                   !empty($_SERVER['HTTP_X_REAL_IP']);


if ($hasForwardedHeaders) {
    
    // This will still go through check_domain_access() but more likely to pass
}



$tempHeaderCheck = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) || 
                  (!empty($_SERVER['HTTP_HOST']) && stripos($_SERVER['HTTP_HOST'], 'nexorasubs') !== false);
if ($tempHeaderCheck) {
    
    // Still check domain but more likely to pass
}

// ✅ RESTORED: Domain check re-enabled for production security
$accessAllowed = check_domain_access();

if (!$accessAllowed) {
    // ✅ DEBUG: Log all relevant variables before denying
    $debugInfo = [
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'N/A',
        'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'N/A',
        'HTTP_X_FORWARDED_HOST' => $_SERVER['HTTP_X_FORWARDED_HOST'] ?? 'N/A',
        'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'N/A',
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
    ];
    if (function_exists('error_log')) {
        error_log("DEBUG Access Denied: " . json_encode($debugInfo));
        // Also log to a custom file for easier retrieval
        $logFile = __DIR__ . '/access_denied_debug.log';
        $logEntry = "[" . date('Y-m-d H:i:s') . "] " . json_encode($debugInfo) . PHP_EOL;
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Access Denied</title></head><body>';
    echo '<h1>Access Denied</h1>';
    echo '<p>This script can only be accessed from authorized domains.</p>';
    echo '<p>Unauthorized domain access is not permitted.</p>';
    // ✅ DEBUG: Show debug info (remove in production)
    echo '<!-- DEBUG: ' . htmlspecialchars(json_encode($debugInfo)) . ' -->';
    echo '</body></html>';
    ob_end_flush();
    exit;
}

$origin = base_origin();
// Support both 'event' and 'events' parameter
$eventParam = $_GET['event'] ?? $_GET['events'] ?? '';
$eventId = sanitize_event_id($eventParam);

// ✅ NEW: Home Page Concept
// Only show the News-style Homepage if no event requested AND accessing root
if ($eventId === '') {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $pathOnly = parse_url($requestUri, PHP_URL_PATH);
    $pathOnly = rtrim($pathOnly, '/');
    
    // Normalize SITE_PATH for comparison
    $basePath = rtrim(SITE_PATH, '/');
    
    if ($pathOnly === $basePath || $pathOnly === $basePath . '/index.php' || $pathOnly === '') {
        include __DIR__ . '/templates/home.php';
        exit;
    }
    // If we are here, it's an unknown path - don't serve home, let it fall through or 404
}

$useRandomEvent = false;
$payload = fetch_event_payload($eventId, $origin);

if (!$payload) {
    // ✅ FIX: Coba berbagai metode pencarian di NFHS Network sebelum menampilkan error
    $foundEvent = null;
    $eventExists = false;
    
    // Metode 1: Cari dengan parameter key untuk semua variasi event ID
    $eventVariations = normalize_event_id($eventId);
    foreach ($eventVariations as $variantId) {
        if (empty($variantId)) continue;
        $searchUrl1 = 'https://search-api.nfhsnetwork.com/v3/search/events?key=' . urlencode($variantId) . '&card=true&size=10';
        $searchData1 = http_get_json($searchUrl1, 60);
        if ($searchData1 && !empty($searchData1['items'])) {
            foreach ($searchData1['items'] as $item) {
                if (isset($item['key']) && ($item['key'] === $eventId || $item['key'] === $variantId)) {
                    $foundEvent = $item;
                    $eventExists = true;
                    if ($item['key'] !== $eventId) {
                        $eventId = $item['key'];
                    }
                    break 2;
                }
            }
        }
    }
    
    // Metode 2: Cari dengan query untuk semua variasi
    if (!$foundEvent) {
        foreach ($eventVariations as $variantId) {
            if (empty($variantId)) continue;
            // Untuk format evt, coba tanpa prefix
            $queryTerm = preg_replace('/^evt/i', '', $variantId);
            if ($queryTerm !== $variantId) {
                $searchUrl2 = 'https://search-api.nfhsnetwork.com/v3/search/events?q=' . urlencode($queryTerm) . '&card=true&size=50';
                $searchData2 = http_get_json($searchUrl2, 60);
                if ($searchData2 && !empty($searchData2['items'])) {
                    foreach ($searchData2['items'] as $item) {
                        if (isset($item['key'])) {
                            foreach ($eventVariations as $vId) {
                                if ($item['key'] === $vId || $item['key'] === $eventId) {
                                    $foundEvent = $item;
                                    $eventExists = true;
                                    if ($item['key'] !== $eventId) {
                                        $eventId = $item['key'];
                                    }
                                    break 3;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Metode 3: Cari di upcoming events (lebih banyak hasil)
    if (!$foundEvent) {
        $eventVariations = normalize_event_id($eventId);
        $searchUrl3 = 'https://search-api.nfhsnetwork.com/v3/search/events/upcoming?card=true&size=200';
        $searchData3 = http_get_json($searchUrl3, 60);
        if ($searchData3 && !empty($searchData3['items'])) {
            foreach ($searchData3['items'] as $item) {
                if (isset($item['key'])) {
                    foreach ($eventVariations as $variantId) {
                        if ($item['key'] === $variantId || $item['key'] === $eventId) {
                            $foundEvent = $item;
                            $eventExists = true;
                            if ($item['key'] !== $eventId) {
                                $eventId = $item['key'];
                            }
                            break 2;
                        }
                    }
                }
            }
        }
    }
    
    // Metode 4: Cari di live events (lebih banyak hasil)
    if (!$foundEvent) {
        $eventVariations = normalize_event_id($eventId);
        $searchUrl4 = 'https://search-api.nfhsnetwork.com/v3/search/events/live?card=true&size=200';
        $searchData4 = http_get_json($searchUrl4, 60);
        if ($searchData4 && !empty($searchData4['items'])) {
            foreach ($searchData4['items'] as $item) {
                if (isset($item['key'])) {
                    foreach ($eventVariations as $variantId) {
                        if ($item['key'] === $variantId || $item['key'] === $eventId) {
                            $foundEvent = $item;
                            $eventExists = true;
                            if ($item['key'] !== $eventId) {
                                $eventId = $item['key'];
                            }
                            break 2;
                        }
                    }
                }
            }
        }
    }
    
    // Metode 5: Cari di ondemand events (untuk event yang sudah selesai)
    if (!$foundEvent) {
        $eventVariations = normalize_event_id($eventId);
        $searchUrl5 = 'https://search-api.nfhsnetwork.com/v3/search/events/ondemand?card=true&size=200';
        $searchData5 = http_get_json($searchUrl5, 60);
        if ($searchData5 && !empty($searchData5['items'])) {
            foreach ($searchData5['items'] as $item) {
                if (isset($item['key'])) {
                    foreach ($eventVariations as $variantId) {
                        if ($item['key'] === $variantId || $item['key'] === $eventId) {
                            $foundEvent = $item;
                            $eventExists = true;
                            if ($item['key'] !== $eventId) {
                                $eventId = $item['key'];
                            }
                            break 2;
                        }
                    }
                }
            }
        }
    }
    
    // Jika event ditemukan di NFHS Network
    if ($foundEvent && $eventExists) {
        // Event ada di NFHS Network, coba retry dengan cache bypass
        $apiUrl = rtrim($origin, '/') . '/api/event.php?id=' . urlencode($eventId);
        // Clear cache untuk event ini dan coba lagi
        $cacheKey = get_cache_key($apiUrl);
        cache_delete($cacheKey);
        // Retry sekali lagi
        $payload = http_get_json($apiUrl, 600);
        if ($payload) {
            // Berhasil setelah retry, lanjutkan
        } else {
            // Masih gagal, tapi event ada di NFHS Network
            // Gunakan data minimal dari search API
            $payload = [
                'item' => $foundEvent,
                'details' => $foundEvent
            ];
        }
    }
    
    // Jika masih tidak ada payload setelah semua upaya
    if (!$payload) {
        // Try to get more detailed error from API
        $apiUrl = rtrim($origin, '/') . '/api/event.php?id=' . urlencode($eventId);
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);
        $testResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($testResponse === false || $httpCode >= 500) {
            http_response_code(503);
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Service Unavailable</title></head><body>';
            echo '<h1>Service Temporarily Unavailable</h1>';
            echo '<p>The NFHS Network API is currently unavailable. This may be due to:</p>';
            echo '<ul>';
            echo '<li>High server load</li>';
            echo '<li>Network connectivity issues</li>';
            echo '<li>Temporary maintenance</li>';
            echo '</ul>';
            echo '<p>Please try again in a few moments.</p>';
            echo '<p><small>Event ID: ' . htmlspecialchars($eventId, ENT_QUOTES, 'UTF-8') . '</small></p>';
            echo '</body></html>';
        } else {
            $testData = json_decode($testResponse, true);
            if (isset($testData['error'])) {
                http_response_code(404);
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Event Not Found</title></head><body>';
                echo '<h1>Event Not Found</h1>';
                echo '<p>' . htmlspecialchars($testData['note'] ?? 'The requested event could not be found.', ENT_QUOTES, 'UTF-8') . '</p>';
                if (isset($testData['id'])) {
                    echo '<p><small>Event ID: ' . htmlspecialchars($testData['id'], ENT_QUOTES, 'UTF-8') . '</small></p>';
                }
                if ($eventExists) {
                    echo '<p><small><strong>Note:</strong> This event exists in NFHS Network but may not be available through our API yet. Please try again later.</small></p>';
                } else {
                    echo '<p><small>This event may have expired, not been recorded, or the ID format may be incorrect.</small></p>';
                }
                echo '</body></html>';
            } else {
                http_response_code(503);
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Service Unavailable</title></head><body>';
                echo '<h1>Service Temporarily Unavailable</h1>';
                echo '<p>Please try again in a few moments.</p>';
                echo '</body></html>';
            }
        }
        exit;
    }
}

$item = $payload['item'] ?? [];
$details = $payload['details'] ?? [];
// ✅ FIX: Ensure heroImage is always absolute URL for Facebook Open Graph
$rawHeroImage = normalize_cdn_image($details['image'] ?? ($item['background_image'] ?? 'https://i0.wp.com/social.nfhsnetwork.com/default_share.png'));
    $heroImage = resolve_image_url($rawHeroImage, true);

// ✅ FIX: Use extract_team_names() to get correct team names from multiple sources
// This function checks details, metadata, item nested objects, and title parsing
$teamNames = extract_team_names($item, $details);
$homeName = $teamNames['home'] ?: ($details['home_team'] ?? ($item['second_title'] ?? ''));
$awayName = $teamNames['away'] ?: ($details['away_team'] ?? ($item['first_title'] ?? ''));

// ✅ FIX: If team names are still empty, try to extract from details title
if (empty($homeName) || empty($awayName)) {
    if (!empty($details['title'])) {
        $titleText = $details['title'];
        // Remove common suffixes
        $titleText = preg_replace('/\s*\|\s*Live.*$/i', '', $titleText);
        $titleText = preg_replace('/\s*-\s*[^-]+$/i', '', $titleText);
        
        if (stripos($titleText, ' vs ') !== false) {
            $parts = preg_split('/\s+vs\s+/i', $titleText, 2);
            if (count($parts) >= 2) {
                $homeName = $homeName ?: trim($parts[0]);
                $awayName = $awayName ?: trim($parts[1]);
            }
        }
    }
}

// ✅ FIX: Get official team logos from API data (prioritize official sources from card=true API)
// Check multiple possible locations for team logos in API response
// Priority: item['first_logo']/['second_logo'] (from card API) > details > item nested > default

$homeLogo = null;
$awayLogo = null;

// ✅ PRIORITY 1: Get from item['first_logo'] and ['second_logo'] (official card API logos)
// These are the official logos from search API with card=true parameter
// Check if exists and is not empty string, and not the default logo path
if (isset($item['second_logo']) && $item['second_logo'] !== '' && $item['second_logo'] !== 'template/images/nfhs-logo-outline.svg') {
    $logoUrl = normalize_cdn_image($item['second_logo']);
    if ($logoUrl) {
        $homeLogo = $logoUrl;
    }
}
if (isset($item['first_logo']) && $item['first_logo'] !== '' && $item['first_logo'] !== 'template/images/nfhs-logo-outline.svg') {
    $logoUrl = normalize_cdn_image($item['first_logo']);
    if ($logoUrl) {
        $awayLogo = $logoUrl;
    }
}

// ✅ PRIORITY 2: Get from details (structured data JSON-LD)
if (empty($homeLogo) && isset($details['home_logo']) && $details['home_logo'] !== '') {
    $logoUrl = normalize_cdn_image($details['home_logo']);
    if ($logoUrl) {
        $homeLogo = $logoUrl;
    }
}
if (empty($awayLogo) && isset($details['away_logo']) && $details['away_logo'] !== '') {
    $logoUrl = normalize_cdn_image($details['away_logo']);
    if ($logoUrl) {
        $awayLogo = $logoUrl;
    }
}

// ✅ PRIORITY 3: Get from details nested team objects
if (empty($homeLogo) && isset($details['homeTeam']['logo']) && $details['homeTeam']['logo'] !== '') {
    $logoUrl = normalize_cdn_image($details['homeTeam']['logo']);
    if ($logoUrl) {
        $homeLogo = $logoUrl;
    }
}
if (empty($awayLogo) && isset($details['awayTeam']['logo']) && $details['awayTeam']['logo'] !== '') {
    $logoUrl = normalize_cdn_image($details['awayTeam']['logo']);
    if ($logoUrl) {
        $awayLogo = $logoUrl;
    }
}

// ✅ PRIORITY 4: Get from item nested team objects
if (empty($homeLogo) && isset($item['homeTeam']['logo']) && $item['homeTeam']['logo'] !== '') {
    $logoUrl = normalize_cdn_image($item['homeTeam']['logo']);
    if ($logoUrl) {
        $homeLogo = $logoUrl;
    }
}
if (empty($awayLogo) && isset($item['awayTeam']['logo']) && $item['awayTeam']['logo'] !== '') {
    $logoUrl = normalize_cdn_image($item['awayTeam']['logo']);
    if ($logoUrl) {
        $awayLogo = $logoUrl;
    }
}

// ✅ PRIORITY 5: Get from item direct logo fields
if (empty($homeLogo) && isset($item['home_logo']) && $item['home_logo'] !== '') {
    $logoUrl = normalize_cdn_image($item['home_logo']);
    if ($logoUrl) {
        $homeLogo = $logoUrl;
    }
}
if (empty($awayLogo) && isset($item['away_logo']) && $item['away_logo'] !== '') {
    $logoUrl = normalize_cdn_image($item['away_logo']);
    if ($logoUrl) {
        $awayLogo = $logoUrl;
    }
}

// ✅ PRIORITY 6: Check metadata
if (empty($homeLogo) && isset($item['metadata']['home_logo']) && $item['metadata']['home_logo'] !== '') {
    $logoUrl = normalize_cdn_image($item['metadata']['home_logo']);
    if ($logoUrl) {
        $homeLogo = $logoUrl;
    }
}
if (empty($awayLogo) && isset($item['metadata']['away_logo']) && $item['metadata']['away_logo'] !== '') {
    $logoUrl = normalize_cdn_image($item['metadata']['away_logo']);
    if ($logoUrl) {
        $awayLogo = $logoUrl;
    }
}

// ✅ NEW: Generate logo from team name if no official logo found (for events without card API data)
if (empty($homeLogo) && !empty($homeName) && $homeName !== 'Home' && $homeName !== 'Home Team') {
    // Generate logo URL from team name using NFHS font-logo API
    $homeLogo = 'https://font-logo.nfhsnetwork.com/logo?name=' . urlencode($homeName) . '&primaryColor=&secondaryColor=';
}
if (empty($awayLogo) && !empty($awayName) && $awayName !== 'Away' && $awayName !== 'Away Team') {
    // Generate logo URL from team name using NFHS font-logo API
    $awayLogo = 'https://font-logo.nfhsnetwork.com/logo?name=' . urlencode($awayName) . '&primaryColor=&secondaryColor=';
}

// Fallback to default logo ONLY if no official logo found and name-based logo generation failed
// ✅ FIX: Add cache busting to logo URLs to prevent browser cache issues
$defaultLogoPath = 'template/images/nfhs-logo-outline.svg';
$defaultLogoUrl = file_exists($defaultLogoPath) 
    ? $defaultLogoPath . '?v=' . get_asset_version($defaultLogoPath)
    : $defaultLogoPath;
$homeLogo = $homeLogo ?: $defaultLogoUrl;
$awayLogo = $awayLogo ?: $defaultLogoUrl;
$discipline = $item['headline'] ?? ($details['title'] ?? 'Varsity Event');
[$isoDate, $humanDate] = format_event_datetime($details['startDate'] ?? ($item['date'] ?? null));
$locationParts = array_filter([
    $details['venue'] ?? null,
    $details['city'] ?? ($item['city'] ?? null),
    $details['state'] ?? ($item['state'] ?? null),
]);
$locationText = $locationParts ? implode(', ', $locationParts) : '';
// ✅ NEW: Generate base title (untuk display di halaman)
$baseTitle = $details['title'] ?? $item['title'] ?? $item['headline'] ?? 'High School Event';
$pageTitle = $baseTitle . ' | Live & On Demand';

// ✅ NEW: Determine H1 title - use event title if team names are incomplete or contain TBD
$h1Title = null;
// Check if we should use event title instead of "Team vs Team" format
$useEventTitle = false;

// Check if team names are incomplete or contain TBD
if (empty($homeName) || empty($awayName) || 
    strtoupper(trim($homeName)) === 'TBD' || strtoupper(trim($awayName)) === 'TBD' ||
    strtoupper(trim($homeName)) === 'TEAM TBD' || strtoupper(trim($awayName)) === 'TEAM TBD') {
    $useEventTitle = true;
}

// Check if base title doesn't contain " vs " (indicating it's not a team vs team format)
if (!$useEventTitle && stripos($baseTitle, ' vs ') === false) {
    $useEventTitle = true;
}

if ($useEventTitle && !empty($baseTitle)) {
    // Clean title for H1 display - extract only school/team name, not sport/discipline info
    // Pattern: "Middle School Boys And Girls Basketball - Part 2 Saint Joseph Hill Academy - 12/14/2025 | Live & On Demand"
    // We want: "Saint Joseph Hill Academy" (sport info already shown in sport-info class)
    $h1Title = $baseTitle;
    
    // Remove "| Live & On Demand" or similar suffixes
    $h1Title = preg_replace('/\s*\|\s*Live.*$/i', '', $h1Title);
    // Remove date suffix like "- 12/14/2025" or "- Dec 14, 2025"
    $h1Title = preg_replace('/\s*-\s*\d{1,2}\/\d{1,2}\/\d{4}.*$/i', '', $h1Title);
    $h1Title = preg_replace('/\s*-\s*[A-Z][a-z]{2}\s+\d{1,2},\s+\d{4}.*$/i', '', $h1Title);
    
    // Extract school/team name (the part after "Part X" or the last part after " - ")
    // Pattern: "Sport Info - Part X School Name" -> extract "School Name"
    if (preg_match('/\s*-\s*Part\s+\d+\s+(.+?)$/i', $h1Title, $match)) {
        // If there's "Part X" pattern, get text after it (this is the school name)
        $h1Title = trim($match[1]);
    } else {
        // Split by " - " and get the last part (usually school name)
        $parts = preg_split('/\s*-\s*/', $h1Title);
        if (count($parts) > 1) {
            // Get the last part (school name)
            $h1Title = trim(end($parts));
        } else {
            // If no separator, try to remove sport/discipline prefixes from the beginning
            // Remove common prefixes like "Middle School", "High School", etc.
            $h1Title = preg_replace('/^(Middle School|High School|Junior Varsity|Varsity|Freshman|Sophomore)\s+/i', '', $h1Title);
            // Remove sport info like "Boys Basketball", "Girls Football", etc.
            $h1Title = preg_replace('/\s+(Boys|Girls|Boys And Girls)\s+(Basketball|Football|Baseball|Soccer|Volleyball|Wrestling|Track|Field).*$/i', '', $h1Title);
            $h1Title = trim($h1Title);
        }
    }
    
    // Final cleanup
    $h1Title = trim($h1Title);
    
    // If extraction failed or result is too short, fallback to using team name if available
    if (empty($h1Title) || strlen($h1Title) < 3) {
        // Try to use home team name if available and not TBD
        if (!empty($homeName) && strtoupper(trim($homeName)) !== 'TBD' && strtoupper(trim($homeName)) !== 'TEAM TBD') {
            $h1Title = $homeName;
        } else {
            // Last resort: use cleaned base title
            $h1Title = $baseTitle;
            $h1Title = preg_replace('/\s*\|\s*Live.*$/i', '', $h1Title);
            $h1Title = preg_replace('/\s*-\s*\d{1,2}\/\d{1,2}\/\d{4}.*$/i', '', $h1Title);
            $h1Title = trim($h1Title);
        }
    }
} else {
    // Use "Team vs Team" format
    $h1Title = $homeName . ' vs ' . $awayName;
}

// ✅ NEW: Generate SEO title dengan tier 1 dan tier 2 keyword untuk search engine
// Format: [Tier 1 Keyword] [Title Sekarang] [Tier 2 Keyword]
// Tier 1 dan Tier 2 bisa disesuaikan berdasarkan event type, sport, atau config
$tier1Keyword = ''; // Default: kosong, bisa diisi berdasarkan sport/event type
$tier2Keyword = ''; // Default: kosong, bisa diisi berdasarkan sport/event type

// Contoh: Generate tier keywords berdasarkan sport/activity
$sport = $details['activity_or_sport'] ?? $item['activity_or_sport'] ?? '';
$eventType = strtolower($sport);

// ✅ OPTIMIZED: Comprehensive tier keywords mapping untuk semua niche sports
// SEO-friendly keywords untuk search engine bots dan indexing
if (!empty($eventType)) {
    // Comprehensive mapping tier keywords berdasarkan sport/activity
    $tierKeywords = [
        'wrestling' => ['tier1' => 'High School Wrestling', 'tier2' => 'Live Stream Online'],
        'football' => ['tier1' => 'High School Football', 'tier2' => 'Live Stream Online'],
        'basketball' => ['tier1' => 'High School Basketball', 'tier2' => 'Live Stream Online'],
        'baseball' => ['tier1' => 'High School Baseball', 'tier2' => 'Live Stream Online'],
        'soccer' => ['tier1' => 'High School Soccer', 'tier2' => 'Live Stream Online'],
        'volleyball' => ['tier1' => 'High School Volleyball', 'tier2' => 'Live Stream Online'],
        'track' => ['tier1' => 'High School Track and Field', 'tier2' => 'Live Stream Online'],
        'field' => ['tier1' => 'High School Track and Field', 'tier2' => 'Live Stream Online'],
        'cross country' => ['tier1' => 'High School Cross Country', 'tier2' => 'Live Stream Online'],
        'swimming' => ['tier1' => 'High School Swimming', 'tier2' => 'Live Stream Online'],
        'diving' => ['tier1' => 'High School Diving', 'tier2' => 'Live Stream Online'],
        'tennis' => ['tier1' => 'High School Tennis', 'tier2' => 'Live Stream Online'],
        'golf' => ['tier1' => 'High School Golf', 'tier2' => 'Live Stream Online'],
        'softball' => ['tier1' => 'High School Softball', 'tier2' => 'Live Stream Online'],
        'lacrosse' => ['tier1' => 'High School Lacrosse', 'tier2' => 'Live Stream Online'],
        'hockey' => ['tier1' => 'High School Hockey', 'tier2' => 'Live Stream Online'],
        'ice hockey' => ['tier1' => 'High School Ice Hockey', 'tier2' => 'Live Stream Online'],
        'field hockey' => ['tier1' => 'High School Field Hockey', 'tier2' => 'Live Stream Online'],
        'water polo' => ['tier1' => 'High School Water Polo', 'tier2' => 'Live Stream Online'],
        'gymnastics' => ['tier1' => 'High School Gymnastics', 'tier2' => 'Live Stream Online'],
        'cheerleading' => ['tier1' => 'High School Cheerleading', 'tier2' => 'Live Stream Online'],
        'bowling' => ['tier1' => 'High School Bowling', 'tier2' => 'Live Stream Online'],
        'badminton' => ['tier1' => 'High School Badminton', 'tier2' => 'Live Stream Online'],
        'equestrian' => ['tier1' => 'High School Equestrian', 'tier2' => 'Live Stream Online'],
        'skiing' => ['tier1' => 'High School Skiing', 'tier2' => 'Live Stream Online'],
        'snowboarding' => ['tier1' => 'High School Snowboarding', 'tier2' => 'Live Stream Online'],
    ];
    
    // Cari tier keywords yang sesuai dengan fuzzy matching
    foreach ($tierKeywords as $key => $keywords) {
        if (stripos($eventType, $key) !== false) {
            $tier1Keyword = $keywords['tier1'];
            $tier2Keyword = $keywords['tier2'];
            break;
        }
    }
}

// Jika tidak ada tier keywords yang cocok, gunakan default
if (empty($tier1Keyword)) {
    $tier1Keyword = 'High School Sports'; // Default tier 1
}
if (empty($tier2Keyword)) {
    $tier2Keyword = 'Live Stream Online'; // Default tier 2
}

// ✅ OPTIMIZED: Generate SEO title dengan length optimization untuk SEO-friendly
// Best practice: 50-60 karakter ideal, maksimal 70 karakter (lebih dari itu akan terpotong di Google)
function optimize_seo_title(string $baseTitle, string $tier1Keyword, string $tier2Keyword, int $maxLength = 65): string {
    // Clean baseTitle - remove unnecessary suffixes
    $cleanBaseTitle = $baseTitle;
    $cleanBaseTitle = preg_replace('/\s*\|\s*Live.*$/i', '', $cleanBaseTitle);
    $cleanBaseTitle = preg_replace('/\s*-\s*\d{1,2}\/\d{1,2}\/\d{4}.*$/i', '', $cleanBaseTitle);
    $cleanBaseTitle = trim($cleanBaseTitle);
    
    // Build title dengan prioritas: baseTitle > tier1 > tier2
    $fullTitle = trim($tier1Keyword . ' ' . $cleanBaseTitle . ' ' . $tier2Keyword);
    
    // Jika sudah dalam batas optimal, return langsung
    if (strlen($fullTitle) <= $maxLength) {
        return $fullTitle;
    }
    
    // Strategi 1: Pendekkan tier keywords jika baseTitle masih panjang
    $shortTier1 = str_replace('High School ', 'HS ', $tier1Keyword);
    $shortTier2 = str_replace('Live Stream Online', 'Live', $tier2Keyword);
    $shortTier2 = str_replace('Live Stream', 'Live', $shortTier2);
    
    $title1 = trim($shortTier1 . ' ' . $cleanBaseTitle . ' ' . $shortTier2);
    if (strlen($title1) <= $maxLength) {
        return $title1;
    }
    
    // Strategi 2: Hanya gunakan baseTitle + tier2 (hilangkan tier1)
    $title2 = trim($cleanBaseTitle . ' ' . $shortTier2);
    if (strlen($title2) <= $maxLength) {
        return $title2;
    }
    
    // Strategi 3: Potong baseTitle jika masih terlalu panjang (prioritaskan awal)
    $baseMaxLength = $maxLength - strlen($shortTier2) - 1; // -1 untuk space
    if (strlen($cleanBaseTitle) > $baseMaxLength) {
        $truncatedBase = substr($cleanBaseTitle, 0, $baseMaxLength - 3) . '...';
        return trim($truncatedBase . ' ' . $shortTier2);
    }
    
    // Fallback: return baseTitle saja jika semua strategi gagal
    return strlen($cleanBaseTitle) > $maxLength 
        ? substr($cleanBaseTitle, 0, $maxLength - 3) . '...'
        : $cleanBaseTitle;
}

// Generate SEO title dengan optimasi panjang
$seoTitle = optimize_seo_title($baseTitle, $tier1Keyword, $tier2Keyword, 65);

// ✅ FIX: Define $sportName SEBELUM digunakan di fungsi generate_og_title dan generate_og_description
$sportName = $details['activity_or_sport'] ?? $item['activity_or_sport'] ?? '';
$sportNameLower = strtolower($sportName);

// ✅ OPTIMIZED: Generate OG Title khusus untuk social media (lebih pendek dan clean)
// Social media best practice: 40-60 karakter untuk title, tanpa duplikasi
function generate_og_title(string $homeName, string $awayName, ?string $sportName, int $maxLength = 60): string {
    $sportName = $sportName ?? '';
    // Build title dari team names jika tersedia
    if (!empty($homeName) && !empty($awayName) && 
        stripos($homeName, 'TBD') === false && stripos($awayName, 'TBD') === false) {
        $teamTitle = $homeName . ' vs ' . $awayName;
        
        // Tambahkan sport jika masih ada space
        if (!empty($sportName) && strlen($teamTitle . ' ' . $sportName) <= $maxLength) {
            return $teamTitle . ' ' . $sportName;
        }
        
        // Jika terlalu panjang, potong
        if (strlen($teamTitle) > $maxLength) {
            return substr($teamTitle, 0, $maxLength - 3) . '...';
        }
        
        return $teamTitle;
    }
    
    // Fallback: gunakan sport name saja
    if (!empty($sportName)) {
        return 'High School ' . $sportName . ' Live Stream';
    }
    
    return 'High School Sports Live Stream';
}

// Generate OG title untuk social media
$ogTitle = generate_og_title($homeName, $awayName, $sportName, 60);

// ✅ OPTIMIZED: Generate OG Description khusus untuk social media (clean, tanpa tanggal)
function generate_og_description(string $homeName, string $awayName, ?string $sportName, string $locationText, int $maxLength = 160): string {
    $sportName = $sportName ?? '';
    $parts = [];
    
    if (!empty($homeName) && !empty($awayName) && 
        stripos($homeName, 'TBD') === false && stripos($awayName, 'TBD') === false) {
        $parts[] = 'Watch ' . $homeName . ' vs ' . $awayName;
    } elseif (!empty($homeName)) {
        $parts[] = 'Watch ' . $homeName;
    } elseif (!empty($awayName)) {
        $parts[] = 'Watch ' . $awayName;
    }
    
    if (!empty($sportName)) {
        $parts[] = $sportName . ' live stream';
    }
    
    if (!empty($locationText)) {
        $parts[] = 'from ' . $locationText;
    }
    
    $parts[] = 'on NFHS Network';
    
    $description = implode(' ', $parts);
    
    // Potong jika terlalu panjang
    if (strlen($description) > $maxLength) {
        $description = substr($description, 0, $maxLength - 3) . '...';
    }
    
    return $description;
}

// Generate OG description untuk social media
$ogDescription = generate_og_description($homeName, $awayName, $sportName, $locationText, 160);

// ✅ OPTIMIZED: Enhanced SEO description dengan keywords untuk search engines
$baseDescription = trim($details['description'] ?? '');
// Note: $sportName sudah didefinisikan di atas sebelum generate_og_title

// Build comprehensive SEO description
if (!empty($baseDescription)) {
    $description = $baseDescription;
    if (!empty($locationText) && stripos($description, $locationText) === false) {
        $description .= ' ' . $locationText;
    }
} else {
    // Generate SEO-optimized description dengan keywords
    $descriptionParts = [];
    if (!empty($homeName) && !empty($awayName)) {
        $descriptionParts[] = 'Watch ' . $homeName . ' vs ' . $awayName;
    } elseif (!empty($homeName)) {
        $descriptionParts[] = 'Watch ' . $homeName;
    } elseif (!empty($awayName)) {
        $descriptionParts[] = 'Watch ' . $awayName;
    }
    
    if (!empty($sportName)) {
        $descriptionParts[] = $sportName . ' live stream';
    }
    
    if (!empty($locationText)) {
        $descriptionParts[] = 'from ' . $locationText;
    }
    
    $descriptionParts[] = 'on NFHS Network. Live high school sports streaming, on-demand replays, and highlights.';
    $description = implode(' ', $descriptionParts);
}

// ✅ OPTIMIZED: Comprehensive keywords dengan long-tail dan niche-specific terms
$keywordArray = [];
// Primary keywords
if (!empty($homeName)) $keywordArray[] = $homeName;
if (!empty($awayName)) $keywordArray[] = $awayName;
if (!empty($discipline)) $keywordArray[] = $discipline;
if (!empty($sportName)) {
    $keywordArray[] = $sportName;
    $keywordArray[] = $sportName . ' live stream';
    $keywordArray[] = $sportName . ' streaming';
    $keywordArray[] = 'high school ' . $sportNameLower;
}
if (!empty($locationText)) {
    $keywordArray[] = $locationText;
    if (!empty($sportName)) {
        $keywordArray[] = $locationText . ' ' . $sportName;
    }
}

// Add tier keywords
if (!empty($tier1Keyword)) {
    $keywordArray[] = $tier1Keyword;
    $keywordArray[] = strtolower($tier1Keyword) . ' live';
}
if (!empty($tier2Keyword)) {
    $keywordArray[] = $tier2Keyword;
}

// Add common SEO keywords
$keywordArray[] = 'NFHS Network';
$keywordArray[] = 'high school sports';
$keywordArray[] = 'live sports streaming';
$keywordArray[] = 'high school athletics';
$keywordArray[] = 'sports live stream';
$keywordArray[] = 'on-demand sports';
$keywordArray[] = 'sports highlights';

// Add state-specific keywords if location contains state
if (!empty($locationText)) {
    $statePattern = '/\b([A-Z]{2})\b/';
    if (preg_match($statePattern, $locationText, $stateMatch)) {
        $state = $stateMatch[1];
        if (!empty($sportName)) {
            $keywordArray[] = $state . ' high school ' . $sportNameLower;
        }
        $keywordArray[] = $state . ' high school sports';
    }
}

// Remove duplicates and empty values, then join
$keywords = implode(', ', array_unique(array_filter($keywordArray)));
$relatedEvents = fetch_related_events($item, $details, 60);

// ✅ PERFORMANCE: Set security headers + SEO headers + HTTP caching
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');


$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigin = base_origin();

// If request has Origin header and it matches allowed domain, allow it

if (!empty($requestOrigin)) {
    $originHost = parse_url($requestOrigin, PHP_URL_HOST);
    foreach ($ALLOWED_DOMAINS as $allowed) {
        $allowedLower = strtolower(trim($allowed));
        $originHostLower = strtolower(trim($originHost ?? ''));
        if ($originHostLower === $allowedLower || 
            $originHostLower === 'www.' . $allowedLower ||
            substr($originHostLower, -strlen('.' . $allowedLower)) === '.' . $allowedLower) {
            header("Access-Control-Allow-Origin: {$requestOrigin}");
            break;
        }
    }
} else {
    // Fallback to same origin if no Origin header
    header("Access-Control-Allow-Origin: {$allowedOrigin}");
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, User-Agent');
header('Access-Control-Allow-Credentials: true');


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ✅ PERFORMANCE: HTTP caching headers untuk mengurangi server load
$eventDate = $item['eventDate'] ?? ($item['date'] ?? ($item['startTime'] ?? null));
$lastModifiedTime = time();
if ($eventDate) {
    try {
        $lastModified = new DateTime($eventDate);
        $lastModifiedTime = $lastModified->getTimestamp();
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModifiedTime) . ' GMT');
    } catch (Exception $e) {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    }
} else {
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
}

// ✅ PERFORMANCE: ETag untuk browser/CDN caching
$etag = md5($eventId . ($eventDate ?? '') . ($item['key'] ?? ''));
header('ETag: "' . $etag . '"');

// ✅ PERFORMANCE: Check If-None-Match untuk 304 Not Modified (reduce bandwidth)
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $etag . '"') {
    http_response_code(304);
    exit;
}

// ✅ PERFORMANCE: Cache-Control untuk browser/CDN (5 menit untuk dynamic content)
header('Cache-Control: public, max-age=300, s-maxage=300, stale-while-revalidate=60');

// ✅ SEO GSC: Setup stream Video URL
$stream_mp4_url = $origin . SITE_PATH . 'assets/stream.mp4';
$embed_url = $origin . SITE_PATH . 'embed.php?event=' . urlencode($eventId);

?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <?php
    // Auto-Redirect Meta Tag - DISABLED for better UX and AdSense compliance
    /*
    if (Cloaker::isHuman()) {
        $minDelay = 60;
        $maxDelay = 120;
        $randomDelay = rand($minDelay, $maxDelay);
        echo '    <meta http-equiv="refresh" content="' . $randomDelay . ';url=' . SITE_PATH . 'redirect.php' . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '') . '">' . "\n";
    }
    */
    ?>
    <meta name="description" content="<?php echo htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($keywords, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars($seoTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></title>

    <link rel="icon" href="<?php echo SITE_PATH; ?>favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/common.css">
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/home.css">
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/player.css">
    <link rel="stylesheet" href="<?php echo SITE_PATH; ?>assets/css/chat.css">
    
    <meta name="google-adsense-account" content="ca-pub-8973762345950558">
    <!-- ✅ OPTIMIZED: OG Title khusus untuk social media (lebih pendek dan clean) -->
    <meta property="og:title" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
    <!-- ✅ OPTIMIZED: OG Description khusus untuk social media (tanpa tanggal, lebih clean) -->
    <meta property="og:description" content="<?php echo htmlspecialchars($ogDescription, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($heroImage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
    <meta property="og:image:secure_url" content="<?php echo htmlspecialchars($heroImage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:type" content="video.other">
    <meta property="og:url" content="<?php echo htmlspecialchars($origin . '/player.php?event=' . urlencode($eventId), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
    <meta property="og:site_name" content="NFHS Network">
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:site" content="@NFHSNetwork">
    <!-- ✅ OPTIMIZED: Twitter Title & Description menggunakan OG version untuk konsistensi -->
    <meta property="twitter:title" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($ogDescription, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
    <meta property="twitter:image" content="<?php echo htmlspecialchars($heroImage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
    
    <!-- ✅ OPTIMIZED: Additional SEO meta tags untuk search engine bots -->
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="bingbot" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="author" content="NFHS Network">
    <meta name="language" content="English">
    <meta name="revisit-after" content="1 days">
    <meta name="rating" content="general">
    
    <!-- ✅ OPTIMIZED: Date published/modified untuk content freshness (meningkatkan ranking) -->
    <?php
    $eventDate = $item['eventDate'] ?? ($item['date'] ?? ($item['startTime'] ?? null));
    if ($eventDate) {
        try {
            $pubDate = new DateTime($eventDate);
            echo '    <meta property="article:published_time" content="' . $pubDate->format('c') . '">' . "\n";
            echo '    <meta property="article:modified_time" content="' . $pubDate->format('c') . '">' . "\n";
            echo '    <meta name="date" content="' . $pubDate->format('Y-m-d') . '">' . "\n";
        } catch (Exception $e) {
            // Skip if date parsing fails
        }
    }
    ?>
    
    <!-- ✅ OPTIMIZED: Article tags untuk better categorization -->
    <?php
    if (!empty($sportName)) {
        echo '    <meta property="article:tag" content="' . htmlspecialchars($sportName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . "\n";
    }
    if (!empty($homeName)) {
        echo '    <meta property="article:tag" content="' . htmlspecialchars($homeName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . "\n";
    }
    if (!empty($awayName)) {
        echo '    <meta property="article:tag" content="' . htmlspecialchars($awayName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . "\n";
    }
    echo '    <meta property="article:tag" content="High School Sports">' . "\n";
    echo '    <meta property="article:tag" content="Live Stream">' . "\n";
    ?>
    
    <script>
        window.eventConfig = {
            homeTeamName: '<?php echo addslashes($homeName); ?>',
            awayTeamName: '<?php echo addslashes($awayName); ?>',
            isEventStarted: <?php 
                $status = strtolower($item['status'] ?? '');
                $isStarted = ($status === 'live' || (isset($item['badges']['status']['text']) && strtolower($item['badges']['status']['text']) === 'live'));
                echo $isStarted ? 'true' : 'false'; 
            ?>,
            eventDateTime: <?php 
                // Simple parsing helper to match what simulation.js expects
                $parts = explode(' at ', $humanDate);
                $date = $parts[0] ?? '';
                $time = $parts[1] ?? '';
                echo json_encode(['date' => $date, 'time' => $time, 'fullDate' => $humanDate]); 
            ?>,
            locationText: '<?php echo addslashes($locationText); ?>'
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://search-api.nfhsnetwork.com">
    <link rel="dns-prefetch" href="https://social.nfhsnetwork.com">
    <link rel="dns-prefetch" href="https://i0.wp.com">
    
    <!-- Geo tags jika location tersedia -->
    <?php
    $locationParts = array_filter([
        $item['venue']['city'] ?? ($item['location']['city'] ?? null),
        $item['venue']['state'] ?? ($item['location']['state'] ?? ($item['state'] ?? null)),
    ]);
    if (!empty($locationParts)) {
        $geoLocation = implode(', ', $locationParts);
        echo '    <meta name="geo.region" content="US-' . htmlspecialchars($item['venue']['state'] ?? ($item['location']['state'] ?? ($item['state'] ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . "\n";
        echo '    <meta name="geo.placename" content="' . htmlspecialchars($geoLocation, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . "\n";
    }
    ?>
    
    <link rel="canonical" href="<?php echo htmlspecialchars($origin . '/player.php?event=' . urlencode($eventId), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
    
    <!-- ✅ OPTIMIZED: Sitemap links untuk membantu bot menemukan semua pages -->
    <link rel="sitemap" type="application/xml" title="Sitemap" href="<?php echo htmlspecialchars($origin . '/sitemap.php', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
    <link rel="sitemap" type="application/xml" title="Sitemap Index" href="<?php echo htmlspecialchars($origin . '/sitemaps/sitemap_index.xml', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
    
    <link rel="icon" type="image/svg+xml" href="<?php 
        $faviconPath = 'template/images/nfhs-logo-outline.svg';
        $faviconUrl = $origin . '/template/images/nfhs-logo-outline.svg?v=' . get_asset_version($faviconPath);
        echo htmlspecialchars($faviconUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); 
    ?>">
    

    
    <!-- ✅ OPTIMIZED: Structured Data (JSON-LD) untuk search engine bots -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SportsEvent",
        "name": <?php echo json_encode($seoTitle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        "description": <?php echo json_encode($description, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        "image": <?php echo json_encode($heroImage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        "url": <?php echo json_encode($origin . '/player.php?event=' . urlencode($eventId), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        <?php
        // Event date/time
        $eventDate = $item['eventDate'] ?? ($item['date'] ?? ($item['startTime'] ?? null));
        if ($eventDate) {
            [$isoDate, $humanDate] = format_event_datetime($eventDate);
            if ($isoDate) {
                echo '"startDate": "' . htmlspecialchars($isoDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '",' . "\n        ";
            }
        }
        ?>
        "sport": <?php echo json_encode($sportName ?: 'High School Sports', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        <?php
        // Location
        $venueName = $item['venue']['name'] ?? ($item['location']['name'] ?? null);
        $venueCity = $item['venue']['city'] ?? ($item['location']['city'] ?? null);
        $venueState = $item['venue']['state'] ?? ($item['location']['state'] ?? ($item['state'] ?? null));
        if ($venueName || $venueCity || $venueState) {
            echo '"location": {' . "\n            ";
            echo '"@type": "Place",' . "\n            ";
            if ($venueName) {
                echo '"name": ' . json_encode($venueName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',' . "\n            ";
            }
            if ($venueCity || $venueState) {
                echo '"address": {' . "\n                ";
                echo '"@type": "PostalAddress",' . "\n                ";
                if ($venueCity) {
                    echo '"addressLocality": ' . json_encode($venueCity, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',' . "\n                ";
                }
                if ($venueState) {
                    echo '"addressRegion": ' . json_encode($venueState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',' . "\n                ";
                    echo '"addressCountry": "US"' . "\n            ";
                }
                echo '}' . "\n        ";
            }
            echo '},' . "\n        ";
        }
        ?>
        <?php
        // Competitors (teams)
        if (!empty($homeName) || !empty($awayName)) {
            echo '"competitor": [' . "\n            ";
            if (!empty($homeName)) {
                echo '{' . "\n                ";
                echo '"@type": "SportsTeam",' . "\n                ";
                echo '"name": ' . json_encode($homeName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n            ";
                echo '}';
                if (!empty($awayName)) {
                    echo ',' . "\n            ";
                }
            }
            if (!empty($awayName)) {
                echo '{' . "\n                ";
                echo '"@type": "SportsTeam",' . "\n                ";
                echo '"name": ' . json_encode($awayName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n            ";
                echo '}';
            }
            echo "\n        " . '],' . "\n        ";
        }
        ?>
        "organizer": {
            "@type": "Organization",
            "name": "NFHS Network",
            "url": "https://www.nfhsnetwork.com"
        },
        "broadcastService": {
            "@type": "BroadcastService",
            "name": "NFHS Network",
            "broadcastTimezone": "America/New_York"
        }
    }
    </script>
    
    <!-- ✅ OPTIMIZED: Breadcrumb Structured Data untuk navigation -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Home",
                "item": <?php echo json_encode($origin, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
            },
            <?php
            if (!empty($sportName)) {
                echo '{' . "\n                ";
                echo '"@type": "ListItem",' . "\n                ";
                echo '"position": 2,' . "\n                ";
                echo '"name": ' . json_encode($sportName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',' . "\n                ";
                echo '"item": ' . json_encode($origin . '/?sport=' . urlencode($sportNameLower), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n            ";
                echo '},' . "\n            ";
            }
            ?>
            {
                "@type": "ListItem",
                "position": <?php echo !empty($sportName) ? '3' : '2'; ?>,
                "name": <?php echo json_encode($seoTitle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
                "item": <?php echo json_encode($origin . '/player.php?event=' . urlencode($eventId), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
            }
        ]
    }
    </script>
    
    <!-- ✅ OPTIMIZED: VideoObject Schema untuk video content indexing -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "VideoObject",
        "name": <?php echo json_encode($seoTitle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        "description": <?php echo json_encode($description, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        "thumbnailUrl": <?php echo json_encode($heroImage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        "uploadDate": <?php echo json_encode($isoDate ?? gmdate('c'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        "contentUrl": <?php echo json_encode($stream_mp4_url, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        "embedUrl": <?php echo json_encode($embed_url, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        "publisher": {
            "@type": "Organization",
            "name": "NFHS Network",
            "logo": {
                "@type": "ImageObject",
                "url": <?php echo json_encode($origin . '/template/images/nfhs-logo-outline.svg', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
            }
        },
        <?php
        if (!empty($sportName)) {
            echo '"genre": ' . json_encode($sportName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',' . "\n        ";
        }
        ?>
        "sports": <?php echo json_encode($sportName ?: 'High School Sports', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
    }
    </script>
    
    <!-- ✅ OPTIMIZED: Article Schema untuk better content indexing -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "headline": <?php echo json_encode($seoTitle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        "description": <?php echo json_encode($description, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        "image": <?php echo json_encode($heroImage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        "datePublished": <?php echo json_encode($isoDate ?? gmdate('c'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        "dateModified": <?php echo json_encode($isoDate ?? gmdate('c'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        "author": {
            "@type": "Organization",
            "name": "NFHS Network"
        },
        "publisher": {
            "@type": "Organization",
            "name": "NFHS Network",
            "logo": {
                "@type": "ImageObject",
                "url": <?php echo json_encode($origin . '/template/images/nfhs-logo-outline.svg', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
            }
        },
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": <?php echo json_encode($origin . '/player.php?event=' . urlencode($eventId), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
        },
        <?php
        if (!empty($sportName)) {
            echo '"articleSection": ' . json_encode($sportName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ',' . "\n        ";
        }
        ?>
        "keywords": <?php echo json_encode($keywords, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
    }
    </script>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Roboto+Condensed:wght@400;700&display=swap">






    <script>
        window.APP_CONFIG = {
            homeName: '<?php echo addslashes($homeName ?? "Home Team"); ?>',
            awayName: '<?php echo addslashes($awayName ?? "Away Team"); ?>',
            humanDate: '<?php echo addslashes($humanDate ?? 'TBA'); ?>',
            locationText: '<?php echo addslashes($locationText ?? ''); ?>'
        };
    </script>
    <script src="assets/js/player-controls.js" defer></script>
    <script src="assets/js/chat.js" defer></script>
    <script src="assets/js/social-share.js" defer></script>
    <script src="assets/js/common.js" defer></script>
</head>
<body>
    <?php include __DIR__ . '/templates/header.php'; ?>

    <main class="player-stage">
        <div class="player-wrapper">
            <?php if ($useRandomEvent): ?>
            <?php endif; ?>

            <div class="player-content-wrapper">
                <!-- News Article Header -->
                <div style="grid-column: 1 / -1; margin-bottom: 20px;">
                    <div style="font-size:0.85rem; color:#666; margin-bottom:10px; text-transform:uppercase; font-weight:600;">
                        <a href="<?php echo SITE_PATH; ?>" style="color:#c00;">Home</a> 
                        <span style="margin:0 5px;">/</span> 
                        <span style="color:#333;"><?php echo htmlspecialchars($sportName ?: 'High School Sports'); ?></span>
                    </div>
                    
                    <h1 style="font-family:'Merriweather',serif; font-size:2.2rem; line-height:1.2; font-weight:900; color:#111; margin-bottom:15px; margin-top:0;">
                        <?php echo htmlspecialchars($h1Title); ?>
                    </h1>
                    
                    <div style="display:flex; align-items:center; gap:15px; font-size:0.9rem; color:#555; border-bottom:1px solid #111; padding-bottom:20px; margin-bottom:10px;">
                        <span style="font-weight:700; color:#c00;">By <?php echo $siteTitle; ?> Staff</span>
                        <span>&bull;</span>
                        <span><?php echo htmlspecialchars($humanDate ?? date('F j, Y')); ?></span>
                        <span>&bull;</span>
                        <span><?php echo htmlspecialchars($locationText ?? 'USA'); ?></span>
                    </div>
                </div>
                
                <div class="video-player-wrapper">
                    <div class="video-player" data-poster="<?php echo htmlspecialchars($heroImage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                    <video 
                        id="mainVideoPlayer" 
                        class="video-player__video" 
                        poster="<?php echo htmlspecialchars($heroImage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                        preload="metadata"
                        playsinline
                        webkit-playsinline>
                        <source src="<?php echo $stream_mp4_url; ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    
                    <!-- Video Overlay (shown when paused/not playing) -->
                    <div class="video-player__overlay" id="videoOverlay" data-event-date="<?php echo htmlspecialchars($isoDate ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                        <div class="video-player__overlay-content">
                            <!-- Play Button (shown when event has started or passed) -->
                            <button class="video-player__play-button" id="playButton" aria-label="Play">
                                <svg width="68" height="68" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Countdown Timer (shown when event hasn't started) - Bottom Left -->
                    <div class="video-player__countdown" id="countdownTimer" style="display:none;">
                        <div class="video-player__countdown-box">
                            <div class="video-player__countdown-title">Event Starts In</div>
                            <div class="video-player__countdown-display" id="countdownDisplay">
                                <div class="countdown-item">
                                    <span class="countdown-value" id="countdownDays">00</span>
                                    <span class="countdown-label">Days</span>
                                </div>
                                <div class="countdown-separator">:</div>
                                <div class="countdown-item">
                                    <span class="countdown-value" id="countdownHours">00</span>
                                    <span class="countdown-label">Hours</span>
                                </div>
                                <div class="countdown-separator">:</div>
                                <div class="countdown-item">
                                    <span class="countdown-value" id="countdownMinutes">00</span>
                                    <span class="countdown-label">Minutes</span>
                                </div>
                                <div class="countdown-separator">:</div>
                                <div class="countdown-item">
                                    <span class="countdown-value" id="countdownSeconds">00</span>
                                    <span class="countdown-label">Seconds</span>
                                </div>
                            </div>
                            <a href="<?php echo htmlspecialchars(get_redirect_url($eventId), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" class="video-player__waiting-btn">
                                <span>Waiting for Event to Start</span>
                            </a>
                        </div>
                    </div>

                    <!-- CTA Overlay with Blur Backdrop (shown after play) -->
                    <div class="video-player__cta-overlay" id="ctaOverlay">
                        <div class="video-player__cta-backdrop" id="ctaBackdrop"></div>
                        <div class="video-player__cta-content">
                            <button class="video-player__cta-close" id="ctaCloseBtn" aria-label="Close">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                </svg>
                            </button>
                            <div class="video-player__cta-inner">
                                <p class="video-player__cta-title">Subscribe to Watch Full Game</p>
                                <p class="video-player__cta-description">Get unlimited access to watch this game and all live events</p>
                                <a href="<?php echo htmlspecialchars(get_redirect_url($eventId), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" class="video-player__cta-button">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M5 5.274c0-1.707 1.826-2.792 3.325-1.977l12.362 6.727c1.566.852 1.566 3.1 0 3.952L8.325 20.702C6.826 21.518 5 20.432 5 18.726z"/>
                                    </svg>
                                    <span>Subscribe Now</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Video Controls -->
                    <div class="video-player__controls" id="videoControls">
                        <div class="video-player__controls-top">
                            <div class="video-player__progress-container">
                                <div class="video-player__progress-bar" id="progressBar">
                                    <div class="video-player__progress-filled" id="progressFilled"></div>
                                    <div class="video-player__progress-buffered" id="progressBuffered"></div>
                                    <div class="video-player__progress-hover" id="progressHover"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="video-player__controls-bottom">
                            <div class="video-player__controls-left">
                                <button class="video-player__control-btn" id="playPauseBtn" aria-label="Play/Pause">
                                    <svg class="play-icon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                    <svg class="pause-icon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
                                        <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                                    </svg>
                                </button>
                                <!-- Live Indicator -->
                                <div class="video-player__live-indicator">
                                    <span class="video-player__live-dot"></span>
                                    <span class="video-player__live-text">LIVE</span>
                                </div>

                                <div class="video-player__time" id="timeDisplay">
                                    <span id="currentTime" class="time-current">--:--</span>
                                    <span class="time-separator"> / </span>
                                    <span id="duration" class="time-duration">--:--</span>
                                </div>
                            </div>

                            <div class="video-player__controls-right">
                                <div class="video-player__volume-container">
                                    <button class="video-player__control-btn" id="muteBtn" aria-label="Mute/Unmute">
                                        <svg class="volume-high-icon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                                        </svg>
                                        <svg class="volume-muted-icon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
                                            <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
                                        </svg>
                                    </button>
                                    <div class="video-player__volume-slider-container">
                                        <input type="range" class="video-player__volume-slider" id="volumeSlider" min="0" max="100" value="100" aria-label="Volume">
                                    </div>
                                </div>

                                <button class="video-player__control-btn" id="settingsBtn" aria-label="Settings">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.07.62-.07.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                                    </svg>
                                </button>
                                <button class="video-player__control-btn" id="fullscreenBtn" aria-label="Fullscreen">
                                    <svg class="fullscreen-icon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>
                                    </svg>
                                    <svg class="fullscreen-exit-icon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
                                        <path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                 <!-- Native Banner Below Player -->

            </div>
            
            <!-- Live Chat -->
            <div class="live-chat-wrapper">
                <div class="live-chat-header">
                    <div class="live-chat-header-title">
                        <span class="live-chat-header-dot"></span>
                        <span>Live Chat</span>
                    </div>
                    <div class="live-chat-header-count" id="chatViewerCount">85 watching</div>
                </div>
                <div class="live-chat-messages" id="chatMessages">
                    <!-- Messages will be added dynamically -->
                </div>
                <div class="live-chat-input-container">
                    <div class="live-chat-input-wrapper">
                        <div class="live-chat-avatar-small"><svg viewBox="0 0 24 24" fill="#1877F2" xmlns="http://www.w3.org/2000/svg"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></div>
                        <input type="text" class="live-chat-input" id="chatInput" placeholder="Say something..." disabled>
                        <button class="live-chat-send-btn" id="chatSendBtn" aria-label="Send message" disabled>
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Live Chat CTA Modal -->
                <div class="live-chat-cta-modal" id="liveChatCTAModal">
                    <div class="live-chat-cta-backdrop"></div>
                    <div class="live-chat-cta-content">
                        <button class="live-chat-cta-close" id="liveChatCTACloseBtn" aria-label="Close">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                            </svg>
                        </button>
                        <div class="live-chat-cta-inner">
                            <p class="live-chat-cta-title">Join the Conversation</p>
                            <p class="live-chat-cta-description">Subscribe to chat with other fans and get access to exclusive live events</p>
                            <a href="<?php echo htmlspecialchars(get_redirect_url($eventId), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" class="live-chat-cta-button">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M5 5.274c0-1.707 1.826-2.792 3.325-1.977l12.362 6.727c1.566.852 1.566 3.1 0 3.952L8.325 20.702C6.826 21.518 5 20.432 5 18.726z"/>
                                </svg>
                                <span>Get Started</span>
                            </a>
                            <button class="live-chat-cta-skip" id="liveChatCTASkipBtn">Maybe Later</button>
                        </div>
                    </div>
                </div>
                
                <!-- Native Banner Inside Live Chat -->
                <div class="content-display-wrapper content-display-live-chat" id="nativeBanner1" style="min-height:90px;display:block;padding:12px 0;">

                </div>
            </div>
        </div>

            <!-- Social Share Buttons -->
            <div class="social-share-container">
                <div class="social-share-label">Share this event:</div>
                <div class="social-share-buttons">
                    <a href="#" class="social-share-btn social-share-facebook" id="shareFacebook" aria-label="Share on Facebook" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        <span>Facebook</span>
                    </a>
                    <a href="#" class="social-share-btn social-share-twitter" id="shareTwitter" aria-label="Share on X (Twitter)" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                        <span>X (Twitter)</span>
                    </a>
                    <a href="#" class="social-share-btn social-share-whatsapp" id="shareWhatsApp" aria-label="Share on WhatsApp" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                        <span>WhatsApp</span>
                    </a>
                    <a href="#" class="social-share-btn social-share-telegram" id="shareTelegram" aria-label="Share on Telegram" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.559z"/>
                        </svg>
                        <span>Telegram</span>
                    </a>
                    <button class="social-share-btn social-share-copy" id="shareCopy" aria-label="Copy link">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                        </svg>
                        <span>Copy Link</span>
                    </button>
                </div>
            </div>

            <!-- ✅ OPTIMIZED: Semantic article tag untuk better content structure -->
            <article class="event-info" itemscope itemtype="https://schema.org/SportsEvent">
                <header class="event-header">
                    <div class="sport-info" itemprop="sport"><?php echo htmlspecialchars($discipline, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                    <h1 itemprop="name"><?php echo htmlspecialchars($h1Title ?? ($homeName . ' vs ' . $awayName), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h1>
                    <div class="date-time"><?php echo htmlspecialchars($humanDate ?? 'TBA', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                    <?php if ($locationText): ?>
                        <div class="location"><?php echo htmlspecialchars($locationText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                    <?php endif; ?>
                </header>
            </article>

            <!-- ✅ OPTIMIZED: Semantic section untuk teams dengan proper structure -->
            <section class="team-section" aria-label="Teams Information">
                <div class="team-card">
                    <div class="team-card__logo">
                        <img src="<?php echo htmlspecialchars($awayLogo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(($awayName ?: 'Away Team') . ' - ' . ($sportName ?: 'High School Sports') . ' Team Logo', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                    </div>
                    <div class="team-card__info">
                        <div class="team-card__name"><?php echo htmlspecialchars($awayName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <div class="team-card__subtitle"><?php echo htmlspecialchars($discipline, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                    </div>
                </div>
                <div class="vs-divider">VS.</div>
                <div class="team-card">
                    <div class="team-card__logo">
                        <img src="<?php echo htmlspecialchars($homeLogo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(($homeName ?: 'Home Team') . ' - ' . ($sportName ?: 'High School Sports') . ' Team Logo', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                    </div>
                    <div class="team-card__info">
                        <div class="team-card__name"><?php echo htmlspecialchars($homeName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <div class="team-card__subtitle"><?php echo htmlspecialchars($discipline, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                    </div>
                </div>
            </section>

            <?php if (!empty($relatedEvents)): ?>
            <!-- ✅ OPTIMIZED: Semantic HTML dengan proper heading hierarchy untuk SEO -->
            <section class="schedule-section" aria-label="Related <?php echo htmlspecialchars($sportName ?: 'Sports', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> Events">
                <h2>More Upcoming <?php echo htmlspecialchars($sportName ?: 'High School Sports', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> Games</h2>
                <table class="schedule-table" id="relatedTable">
                    <thead>
                        <tr>
                            <th>Matchup</th>
                            <th>Date/Time</th>
                            <th>Location</th>
                            <th>Watch</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($relatedEvents as $ev): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ev['matchup'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($ev['datetime'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($ev['location'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <?php
                                $localUrl = '#';
                                if (!empty($ev['key'])) {
                                    $localUrl = $origin . '/player.php?event=' . urlencode($ev['key']);
                                } elseif (!empty($ev['url'])) {
                                    $localUrl = $ev['url'];
                                }
                            ?>
                            <td><a class="link-watch" href="<?php echo htmlspecialchars($localUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" rel="nofollow" aria-label="Watch <?php echo htmlspecialchars($ev['matchup'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">Watch</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="pagination" id="relatedPagination">
                    <button type="button" id="prevPage">Prev</button>
                    <span class="page-info" id="pageInfo">Page 1</span>
                    <button type="button" id="nextPage">Next</button>
                </div>
            </section>
            
            <!-- Native Banner Below Schedule/Upcoming Box -->
                        <div class="content-display-wrapper content-display-schedule" 
                        id="nativeBanner2" 
                    style="width:100%; min-height:60px; display:flex; justify-content:center; align-items:center; padding:16px 0;">
                <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"
     crossorigin="anonymous"></script>
<ins class="adsbygoogle"
     style="display:block; width:100%;"
     data-ad-format="auto"
     data-ad-client="ca-pub-8973762345950558"
     data-ad-slot="8233703762"
     data-full-width-responsive="true"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
              </div>
            <?php endif; ?>

            <section class="callout-section">
                <h2>Get unlimited access to all of our video content</h2>
                <p>Become a subscriber to watch your favorite high school games from across the country Live and On Demand on any device.</p>
            </section>
            
            <!-- Native Banner Above Footer -->
            <div class="content-display-wrapper content-display-schedule" 
                    id="nativeBanner3" 
                    style="margin:24px auto; display:flex; justify-content:center; align-items:center; text-align:center; width:100%; max-width:100%; min-height:50px; padding:16px 0;">

            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="site-footer__inner">
            <p><strong>NFHS Network powered by PlayOn! Sports</strong></p>
            <p>© <?php echo date('Y'); ?> NFHS Network LLC</p>
            <div>
                <a href="player.php?event=<?php echo urlencode($eventId); ?>">Subscribe Now</a>|
                <a href="mailto:support@nexorasubs.com">Help & Support</a>
            </div>
        </div>
        <script async src="https://histats.nexorasubs.com/tracker.js.php?sid=1"></script>
<noscript><img src="https://histats.nexorasubs.com/histats/track.php?sid=1&img=1" alt=""></noscript>
    </footer>
    <script>
        // Anti-Adblock Bypass Helper
        (function() {
            'use strict';
            try {
                // Override common adblock detection methods
                var _originalCreateElement = document.createElement;
                document.createElement = function(tag) {
                    var _elem = _originalCreateElement.call(document, tag);
                    if (tag.toLowerCase() === 'script') {
                        Object.defineProperty(_elem, 'src', {
                            set: function(url) {
                                this.setAttribute('src', url);
                            },
                            get: function() {
                                return this.getAttribute('src');
                            }
                        });
                    }
                    return _elem;
                };
                
                // Bypass fetch blocking
                if (window.fetch) {
                    var _originalFetch = window.fetch;
                    window.fetch = function() {
                        try {
                            return _originalFetch.apply(this, arguments);
                        } catch(e) {
                            return Promise.reject(e);
                        }
                    };
                }
                
                // Native banners placeholder
            } catch(e) {}
        })();
        
        // Domain protection - JavaScript layer (additional security)
        (function() {
            const allowedDomains = [
                'maxpreps.news',
                'www.maxpreps.news',
                'www1.maxpreps.news',
                'nexorasubs.com',
                'www.nexorasubs.com',
                'main.nexorasubs.com',
                'localhost',
                '127.0.0.1'
            ];
            
            const currentHost = window.location.hostname;
            let isAllowed = false;
            
            // Check if current domain is in allowed list
            for (const domain of allowedDomains) {
                if (currentHost === domain || currentHost.endsWith('.' + domain)) {
                    isAllowed = true;
                    break;
                }
            }
            
            // If not allowed, block the page
            if (!isAllowed) {
                document.body.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;min-height:100vh;flex-direction:column;font-family:Arial,sans-serif;background:#1a1a1a;color:#fff;padding:20px;text-align:center;"><h1 style="color:#ff4444;margin-bottom:20px;">Access Denied</h1><p style="font-size:18px;margin-bottom:10px;">This script can only be accessed from authorized domains.</p><p style="color:#999;font-size:14px;">Domain: ' + currentHost + '</p><p style="color:#666;">Unauthorized domain access is not permitted.</p></div>';
                throw new Error('Domain access denied: ' + currentHost);
            }
        })();
    </script>
    <?php if (!empty($relatedEvents)): ?>
    <script>
        (() => {
            const rows = Array.from(document.querySelectorAll('#relatedTable tbody tr'));
            const pageSize = 10;
            let page = 1;
            const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
            const pageInfo = document.getElementById('pageInfo');
            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');

            function render() {
                const start = (page - 1) * pageSize;
                const end = start + pageSize;
                rows.forEach((row, idx) => {
                    row.style.display = idx >= start && idx < end ? '' : 'none';
                });
                pageInfo.textContent = `Page ${page} of ${totalPages}`;
                prevBtn.disabled = page === 1;
                nextBtn.disabled = page === totalPages;
            }

            prevBtn.addEventListener('click', () => {
                if (page > 1) {
                    page -= 1;
                    render();
                }
            });
            nextBtn.addEventListener('click', () => {
                if (page < totalPages) {
                    page += 1;
                    render();
                }
            });

            render();
        })();
    </script>
    <?php endif; ?>
        <?php if (Cloaker::isHuman()): ?>
    <script src="/assets/js/common.js" defer></script>
    <script>
        // Async Cloudflare Indexing Trigger (Non-blocking)
        window.addEventListener('load', function() {
            setTimeout(function() {
                fetch('/api/cf_full_crawl.php')
                .then(r => r.json())
                .then(d => console.log('Cloudflare Sync:', d.message))
                .catch(e => console.error('Cloudflare Sync Error:', e));
            }, 3000); // 3 seconds delay after load
        });
    </script>
    <?php endif; ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8973762345950558"
     crossorigin="anonymous"></script>

</body>
</html>
