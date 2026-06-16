<?php

// ✅ FIX: BOM-safe and capture everything
ob_start();

// ✅ FIX: Disable auto-prepend/append files that might add CSS/JS
if (function_exists('ini_set')) {
    @ini_set('auto_prepend_file', '');
    @ini_set('auto_append_file', '');
    @ini_set('display_errors', '0');
}
error_reporting(0);

// ✅ FIX: Load core helpers and config
require_once __DIR__ . '/includes/helpers.php';
$config = require __DIR__ . '/config.php';
$siteDomain = $config['site_domain'] ?? 'maxpreps.news';

/**
 * Dynamic Sitemap Generator dengan Incremental Merge
 * Generates sitemap.xml from realtime API data (no database required)
 * Incremental update - merge events lama dengan events baru
 *
 * Features:
 * - Fetches ALL events from API (upcoming, live, ondemand)
 * - Merge dengan sitemap yang sudah ada (mempertahankan events lama)
 * - Menambahkan events baru tanpa menghapus yang lama
 * - Supports sitemap index for >10,000 URLs (10,000 per file to prevent timeout)
 * - Bot-friendly: events lama tetap ada untuk indexing
 * - Stream writing untuk memory efficiency
 * - Keyword doorway dari database (incremental, tanpa duplikat)
 */

// ─── Configuration ────────────────────────────────────────────────────────────
$maxUrlsPerSitemap = 10000;
$baseUrlOverride   = 'https://' . $siteDomain;
$cacheFile         = __DIR__ . '/sitemap_cache.xml';
$cacheIndexFile    = __DIR__ . '/sitemap_index_cache.xml';
$cacheDuration     = 3600;
$logFile           = __DIR__ . '/sitemap_generation.log';
$sitemapDir        = __DIR__ . '/sitemaps';

// Database config (Corrected from go/sitemap.php)
$db_config = [
    'host' => 'localhost',
    'name' => 'bray5937_keyword',
    'user' => 'root',
    'pass' => '',
];

// Buat directory sitemaps jika belum ada
if (!is_dir($sitemapDir)) {
    @mkdir($sitemapDir, 0755, true);
}

// ─── Logging ──────────────────────────────────────────────────────────────────
function log_sitemap_generation(string $message, array $data = []): void
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry  = "[{$timestamp}] {$message}";
    if (!empty($data)) {
        $logEntry .= " | Data: " . json_encode($data);
    }
    $logEntry .= "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

function log_cron_sitemap(string $status, string $targetDate, int $eventCount = 0, ?string $error = null): void
{
    $cronLogFile = __DIR__ . '/cron_sitemap.log';
    $timestamp   = date('Y-m-d H:i:s');

    switch ($status) {
        case 'success':
            $logEntry = "[{$timestamp}] ✅ BERHASIL: Sitemap untuk tanggal {$targetDate} berhasil di-generate ({$eventCount} events)\n";
            break;
        case 'failed':
            $errorMsg = $error ? " - Error: {$error}" : '';
            $logEntry = "[{$timestamp}] ❌ GAGAL: Sitemap untuk tanggal {$targetDate} gagal di-generate{$errorMsg}\n";
            break;
        case 'no_events':
            $logEntry = "[{$timestamp}] ⚠️  TIDAK ADA EVENT: Tidak ada event untuk tanggal {$targetDate}\n";
            break;
        case 'skipped':
            $logEntry = "[{$timestamp}] ⏭️  DILEWATI: Tidak ada tanggal yang perlu di-generate\n";
            break;
        default:
            $logEntry = "[{$timestamp}] ℹ️  INFO: {$status} - {$targetDate}\n";
    }

    @file_put_contents($cronLogFile, $logEntry, FILE_APPEND);
}

// ─── Search Engine Ping ────────────────────────────────────────────────────────
function ping_search_engines(string $sitemapUrl): void
{
    $pingServices = [
        'Google' => 'https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl),
        'Bing'   => 'https://www.bing.com/ping?sitemap=' . urlencode($sitemapUrl),
        'Yandex' => 'https://webmaster.yandex.com/ping?sitemap=' . urlencode($sitemapUrl),
    ];

    foreach ($pingServices as $serviceName => $pingUrl) {
        $success   = false;
        $httpCode  = 0;
        $curlError = '';
        $curlErrno = 0;
        $method    = 'cURL';

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $ch = curl_init($pingUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; SitemapPing/1.0)',
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_ENCODING       => '',
            ]);

            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $success = true;
                break;
            }

            // SSL fallback
            if ($curlErrno === CURLE_SSL_CONNECT_ERROR || $curlErrno === CURLE_SSL_CERTPROBLEM || $httpCode === 0) {
                $ch = curl_init($pingUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 15,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; SitemapPing/1.0)',
                    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                ]);

                $response  = curl_exec($ch);
                $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                $curlErrno = curl_errno($ch);
                curl_close($ch);

                if ($httpCode >= 200 && $httpCode < 300) {
                    $success = true;
                    break;
                }
            }

            if ($attempt < 2) {
                usleep(500000);
            }
        }

        // Fallback ke file_get_contents
        if (!$success && ini_get('allow_url_fopen')) {
            $context  = stream_context_create([
                'http' => [
                    'method'        => 'GET',
                    'timeout'       => 10,
                    'user_agent'    => 'Mozilla/5.0 (compatible; SitemapPing/1.0)',
                    'ignore_errors' => true,
                ],
                'ssl'  => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $response = @file_get_contents($pingUrl, false, $context);
            if ($response !== false) {
                if (isset($http_response_header)) {
                    foreach ($http_response_header as $header) {
                        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                            $httpCode = (int) $matches[1];
                            if ($httpCode >= 200 && $httpCode < 300) {
                                $success = true;
                                $method  = 'file_get_contents';
                                break;
                            }
                        }
                    }
                } elseif (strlen($response) > 0) {
                    $success  = true;
                    $httpCode = 200;
                    $method   = 'file_get_contents';
                }
            }
        }

        if ($success) {
            log_sitemap_generation("✅ Pinged {$serviceName} successfully via {$method}", [
                'ping_url'  => $pingUrl,
                'http_code' => $httpCode,
            ]);
        } else {
            $errorMessage = $curlError ?: 'Connection failed';
            if ($curlErrno > 0) {
                $errorMessage .= ' (CURL Error #' . $curlErrno . ': ' . curl_strerror($curlErrno) . ')';
            }
            log_sitemap_generation("❌ Failed to ping {$serviceName}", [
                'ping_url'   => $pingUrl,
                'http_code'  => $httpCode,
                'error'      => $errorMessage,
                'curl_errno' => $curlErrno,
            ]);
        }
    }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

// ✅ FIX: Deteksi CLI mode (dipakai di banyak tempat)
// ✅ FIX: Robust CLI detection
$isCli = PHP_SAPI === 'cli' || (!isset($_SERVER['HTTP_HOST']) && defined('STDIN'));

function base_origin(array $config): string
{
    global $baseUrlOverride, $isCli;

    $host       = $_SERVER['HTTP_HOST'] ?? '';
    $serverName = $_SERVER['SERVER_NAME'] ?? '';

    if ($isCli) {
        return !empty($baseUrlOverride)
            ? rtrim($baseUrlOverride, '/')
            : 'https://' . ($config['site_domain'] ?? 'maxpreps.news');
    }

    if (!empty($host)) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = preg_replace('/:\d+$/', '', $host);
        return $scheme . '://' . $host;
    }

    if (!empty($serverName) && $serverName !== 'localhost' && $serverName !== '127.0.0.1') {
        $scheme     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $serverName = preg_replace('/:\d+$/', '', $serverName);
        return $scheme . '://' . $serverName;
    }

    return 'https://' . ($config['site_domain'] ?? 'maxpreps.news');
}

function parse_memory_limit(string $memoryLimit): int
{
    $memoryLimit = trim($memoryLimit);
    $unit        = strtolower(substr($memoryLimit, -1));
    $value       = (int) $memoryLimit;

    switch ($unit) {
        case 'g':
            return $value * 1024 * 1024 * 1024;
        case 'm':
            return $value * 1024 * 1024;
        case 'k':
            return $value * 1024;
        default:
            return $value;
    }
}

function http_get_json(string $url): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'User-Agent: NFHS-Sitemap/1.0',
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

// ─── Sitemap XML Helpers ───────────────────────────────────────────────────────
function generate_sitemap_url(
    string  $loc,
    ?string $lastmod    = null,
    string  $changefreq = 'daily',
    string  $priority   = '0.8'
): string {
    $lastmodXml = $lastmod ? "    <lastmod>{$lastmod}</lastmod>\n" : '';
    return "  <url>\n"
        . "    <loc>" . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . "</loc>\n"
        . $lastmodXml
        . "    <changefreq>{$changefreq}</changefreq>\n"
        . "    <priority>{$priority}</priority>\n"
        . "  </url>\n";
}

function generate_sitemap_index_entry(string $loc, ?string $lastmod = null): string
{
    $lastmodXml = $lastmod ? "    <lastmod>{$lastmod}</lastmod>\n" : '';
    return "  <sitemap>\n"
        . "    <loc>" . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . "</loc>\n"
        . $lastmodXml
        . "  </sitemap>\n";
}

// ─── Stream Sitemap File Helpers ──────────────────────────────────────────────

/**
 * Buka file sitemap baru dan tulis XML header
 *
 * @return resource
 */
function open_sitemap_file(int $number, string $dir)
{
    $file   = $dir . "/sitemap-{$number}.xml";
    $handle = fopen($file, 'w');
    if ($handle === false) {
        throw new RuntimeException("Cannot open sitemap file for writing: {$file}");
    }
    fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
    fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
        . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
        . ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9'
        . ' http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n");
    return $handle;
}

/**
 * Tutup file sitemap, tambahkan ke array $sitemaps (pass by reference)
 *
 * @param resource $handle
 */
function close_sitemap_file(
    $handle,
    int    $number,
    string $dir,
    string $baseUrl,
    string $now,
    array  &$sitemaps
): void {
    fwrite($handle, '</urlset>');
    fclose($handle);

    $file      = $dir . "/sitemap-{$number}.xml";
    $sitemaps[] = [
        'url'     => $baseUrl . "/sitemaps/sitemap-{$number}.xml",
        'lastmod' => $now,
    ];

    log_sitemap_generation("Sitemap file completed", [
        'number' => $number,
        'size'   => filesize($file),
    ]);
}

// ─── Event Date / Priority Helpers ────────────────────────────────────────────
function extract_event_date(array $event): ?DateTime
{
    $rawItem = $event['raw_item'] ?? null;

    $dateFields = [
        $event['date']           ?? null,
        $event['eventDate']      ?? null,
        $event['startTime']      ?? null,
        $event['start_date']     ?? null,
        $event['event_date']     ?? null,
        $event['scheduledDate']  ?? null,
        $event['scheduled_date'] ?? null,
        $event['gameDate']       ?? null,
        $event['game_date']      ?? null,
    ];

    if ($rawItem && is_array($rawItem)) {
        $dateFields = array_merge($dateFields, [
            $rawItem['date']                       ?? null,
            $rawItem['eventDate']                  ?? null,
            $rawItem['startTime']                  ?? null,
            $rawItem['start_date']                 ?? null,
            $rawItem['event_date']                 ?? null,
            $rawItem['scheduledDate']              ?? null,
            $rawItem['scheduled_date']             ?? null,
            $rawItem['gameDate']                   ?? null,
            $rawItem['game_date']                  ?? null,
            $rawItem['event']['date']              ?? null,
            $rawItem['event']['eventDate']         ?? null,
            $rawItem['event']['startTime']         ?? null,
            $rawItem['event']['start_date']        ?? null,
            $rawItem['event']['event_date']        ?? null,
            $rawItem['schedule']['date']           ?? null,
            $rawItem['schedule']['startTime']      ?? null,
            $rawItem['details']['date']            ?? null,
            $rawItem['details']['eventDate']       ?? null,
        ]);
    }

    foreach ($dateFields as $eventDate) {
        if (!$eventDate || is_array($eventDate)) {
            continue;
        }

        try {
            $dt = new DateTime($eventDate);
            if ($dt) {
                return $dt;
            }
        } catch (Exception $e1) {
            $formats = [
                'Y-m-d H:i:s',
                'Y-m-d H:i',
                'Y-m-d',
                'Y/m/d H:i:s',
                'Y/m/d',
                'm/d/Y H:i:s',
                'm/d/Y',
            ];
            foreach ($formats as $format) {
                $dt = DateTime::createFromFormat($format, $eventDate);
                if ($dt) {
                    return $dt;
                }
            }

            if (is_numeric($eventDate)) {
                try {
                    return new DateTime('@' . $eventDate);
                } catch (Exception $e) {
                    // ignore
                }
            }
        }
    }

    return null;
}

function get_event_priority_changefreq(array $event, ?DateTime $eventDate = null): array
{
    $status = $event['status'] ?? 'ondemand';
    $now    = new DateTime();

    if ($status === 'live') {
        return ['priority' => '1.0', 'changefreq' => 'always'];
    }

    if ($status === 'upcoming') {
        if ($eventDate) {
            $daysUntil = (int) $now->diff($eventDate)->days;
            if ($daysUntil <= 1)  return ['priority' => '0.95', 'changefreq' => 'hourly'];
            if ($daysUntil <= 7)  return ['priority' => '0.90', 'changefreq' => 'daily'];
            return                       ['priority' => '0.85', 'changefreq' => 'daily'];
        }
        return ['priority' => '0.90', 'changefreq' => 'hourly'];
    }

    // ondemand (default)
    if ($eventDate) {
        $daysSince = (int) $now->diff($eventDate)->days;
        if ($daysSince <= 1)  return ['priority' => '0.85', 'changefreq' => 'daily'];
        if ($daysSince <= 7)  return ['priority' => '0.75', 'changefreq' => 'weekly'];
        if ($daysSince <= 30) return ['priority' => '0.70', 'changefreq' => 'weekly'];
        return                       ['priority' => '0.60', 'changefreq' => 'monthly'];
    }

    return ['priority' => '0.70', 'changefreq' => 'weekly'];
}

// ─── Read Existing Sitemap Events ─────────────────────────────────────────────
function read_existing_sitemap_events(string $sitemapDir): array
{
    $existingEvents = [];
    $sitemapFiles   = glob($sitemapDir . '/sitemap-*.xml') ?: [];

    foreach ($sitemapFiles as $file) {
        if (strpos($file, 'sitemap_index') !== false) {
            continue;
        }

        $content = @file_get_contents($file);
        if (!$content) {
            continue;
        }

        preg_match_all(
            '/<url>\s*<loc>https?:\/\/[^\/]+.*\/player\.php\?event=([^<&]+)[^<]*<\/loc>\s*(?:<lastmod>([^<]+)<\/lastmod>)?/i',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $eventKey = urldecode($match[1]);
            $lastmod  = $match[2] ?? null;

            $eventDate = null;
            if ($lastmod) {
                try {
                    $dt        = new DateTime($lastmod);
                    $eventDate = $dt->format('c');
                } catch (Exception $e) {
                    // ignore
                }
            }

            $existingEvents[$eventKey] = [
                'key'     => $eventKey,
                'date'    => $eventDate,
                'lastmod' => $lastmod,
                'status'  => 'ondemand',
            ];
        }
    }

    return $existingEvents;
}

// ─── Fetch Events From API ────────────────────────────────────────────────────
function fetch_events_from_api(string $status, int $maxEvents = PHP_INT_MAX, ?string $targetDate = null): array
{
    $memoryLimit      = ini_get('memory_limit');
    $memoryLimitBytes = $memoryLimit ? parse_memory_limit($memoryLimit) : 128 * 1024 * 1024;
    $memoryUsageStart = memory_get_usage(true);
    $maxMemoryUsage   = $memoryLimitBytes * 0.8;

    $allEvents      = [];
    $start          = 0;
    $size           = 100;
    $maxPages       = 999999;
    $timeoutLimit   = 600;
    $startTime      = time();
    $totalFromApi   = null;
    $emptyPageCount = 0;

    $buildUrl = fn($startPos) => "https://search-api.nfhsnetwork.com/v3/search/events/{$status}?card=true&size={$size}&start={$startPos}";

    for ($page = 0; $page < $maxPages; $page++) {

        if ((time() - $startTime) > $timeoutLimit) {
            log_sitemap_generation("Timeout reached, stopping fetch", [
                'status'        => $status,
                'events_fetched' => count($allEvents),
                'time_elapsed'  => time() - $startTime,
            ]);
            break;
        }

        if ($page % 10 === 0 && $page > 0) {
            $memoryUsed = memory_get_usage(true) - $memoryUsageStart;
            if ($memoryUsed > $maxMemoryUsage) {
                log_sitemap_generation("Memory limit approaching, stopping fetch", [
                    'status'        => $status,
                    'events_fetched' => count($allEvents),
                    'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
                ]);
                break;
            }
        }

        $url     = $buildUrl($start);
        $payload = null;

        for ($retry = 0; $retry < 3; $retry++) {
            if ($retry > 0) {
                sleep((int) pow(2, $retry - 1));
            }
            $payload = http_get_json($url);
            if ($payload && isset($payload['items'])) {
                break;
            }
        }

        if ($payload && isset($payload['total']) && $totalFromApi === null && $payload['total'] > 0) {
            $totalFromApi = (int) $payload['total'];
            log_sitemap_generation("API total events available", [
                'status' => $status,
                'total'  => $totalFromApi,
            ]);
        }

        if (!$payload) {
            if ($start >= 10000) {
                log_sitemap_generation("API limit reached at position 10000", [
                    'status'        => $status,
                    'events_fetched' => count($allEvents),
                ]);
                break;
            }
            $start += $size;
            $emptyPageCount++;
            if ($emptyPageCount >= 10) {
                break;
            }
            continue;
        }

        if (!isset($payload['items']) || !is_array($payload['items'])) {
            $start += $size;
            $emptyPageCount++;
            if ($emptyPageCount >= 10) {
                break;
            }
            continue;
        }

        $total = $payload['total'] ?? $totalFromApi ?? 0;
        if ($totalFromApi === null && $total > 0) {
            $totalFromApi = $total;
        }

        $items = $payload['items'];

        if (count($items) === 0) {
            $emptyPageCount++;
            if ($emptyPageCount >= 10) {
                if ($totalFromApi !== null && count($allEvents) < $totalFromApi * 0.99) {
                    $emptyPageCount = 0;
                    $start += $size;
                    continue;
                }
                break;
            }
            $start += $size;
            continue;
        }

        $emptyPageCount = 0;

        foreach ($items as $item) {
            $eventKey = $item['key'] ?? null;
            if (!$eventKey) {
                continue;
            }

            $eventDate = $item['date']
                ?? $item['eventDate']
                ?? $item['startTime']
                ?? $item['start_date']
                ?? $item['event_date']
                ?? $item['scheduledDate']
                ?? $item['scheduled_date']
                ?? $item['gameDate']
                ?? $item['game_date']
                ?? $item['event']['date']
                ?? $item['event']['eventDate']
                ?? $item['event']['startTime']
                ?? null;

            $allEvents[] = [
                'key'      => $eventKey,
                'status'   => $status,
                'title'    => $item['title'] ?? $item['headline'] ?? null,
                'date'     => $eventDate,
                'raw_item' => $item,
            ];
        }

        $start += $size;

        if ($page % 5 === 0 && $page > 0) {
            $memoryUsed = memory_get_usage(true) - $memoryUsageStart;
            if ($memoryUsed > $maxMemoryUsage) {
                log_sitemap_generation("Memory limit reached, stopping fetch", [
                    'status'        => $status,
                    'events_fetched' => count($allEvents),
                    'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
                ]);
                break;
            }
        }

        if (count($allEvents) % 10000 === 0 && count($allEvents) > 0) {
            log_sitemap_generation("Fetching progress", [
                'status'          => $status,
                'events_fetched'  => count($allEvents),
                'time_elapsed'    => time() - $startTime,
                'total_from_api'  => $totalFromApi,
                'start_position'  => $start,
            ]);
        }

        usleep(50000);
    }

    return $allEvents;
}

// ─── Fetch Keywords from DB (Generator) ───────────────────────────────────────
/**
 * Yields keyword rows satu per satu (memory-efficient).
 * Setiap row: ['key' => slug, 'keyword' => raw keyword, 'type' => 'keyword_doorway', 'status' => 'ondemand']
 */
function fetch_keywords_from_db(array $config): Generator
{
    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4",
            $config['user'],
            $config['pass'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 30,
                // ✅ Unbuffered query untuk dataset besar - hemat memory
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
            ]
        );

        // ✅ FIX: Optimize query. Remove DISTINCT if id is unique.
        // If duplicates exist, maybe use GROUP BY slug instead of DISTINCT keyword,slug
        $stmt = $pdo->query("SELECT slug FROM seo_keywords ORDER BY id ASC");

        $yieldCount = 0;
        while ($row = $stmt->fetch()) {
            // ✅ FIX: Sanitasi slug agar tidak ada leading slash atau spasi
            $slug = ltrim(trim((string) ($row['slug'] ?? '')), '/');
            if ($slug === '') {
                continue; // Skip jika slug kosong
            }

            yield [
                'key'     => $slug,
                'type'    => 'keyword_doorway',
                'status'  => 'ondemand',
            ];

            $yieldCount++;
        }

        log_sitemap_generation("Keywords fetched from DB", ['count' => $yieldCount]);
    } catch (PDOException $e) {
        // ✅ FIX: Log error yang lebih informatif (tidak diam-diam)
        log_sitemap_generation("❌ DB Fetch Error (keywords will be skipped): " . $e->getMessage(), [
            'host' => $config['host'],
            'name' => $config['name'],
            'note' => 'Check DB credentials and that table seo_keywords exists',
        ]);
        // Generator yang gagal tidak yield apa-apa - sitemap tetap dibuat tanpa keywords
    }
}

// ─── Cache Serving (Web Mode Only) ────────────────────────────────────────────
$requestUri  = $_SERVER['REQUEST_URI'] ?? '';
$scriptName  = basename($_SERVER['SCRIPT_NAME'] ?? '');

if (!$isCli) {
    // Serve specific numbered sitemap from cache
    $sitemapFileMatch = [];
    if (
        preg_match('/sitemap-(\d+)\.php/', $requestUri, $sitemapFileMatch) ||
        preg_match('/sitemap-(\d+)\.php/', $scriptName, $sitemapFileMatch)
    ) {
        $requestedSitemapNumber = (int) $sitemapFileMatch[1];
        $specificCacheFile      = __DIR__ . "/sitemap_cache_{$requestedSitemapNumber}.xml";

        if (file_exists($specificCacheFile)) {
            $cachedContent = file_get_contents($specificCacheFile);
            $cleanContent = trim(preg_match('/(.*<\/urlset>)/s', $cachedContent, $m) ? $m[1] : $cachedContent);

            ob_end_clean(); // Final clean before output
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: public, max-age=' . ((time() - filemtime($specificCacheFile)) < $cacheDuration ? '3600' : '300'));
            header('X-Sitemap-Source: cache');
            header('X-Sitemap-Number: ' . $requestedSitemapNumber);
            header('X-Robots-Tag: noindex');

            echo $cleanContent;
            if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
            exit(0);
        }
    }

    // Serve index or main cache
    if (file_exists($cacheIndexFile) && (time() - filemtime($cacheIndexFile)) < $cacheDuration) {
        $cachedContent = file_get_contents($cacheIndexFile);
        $cleanContent = "";
        if (preg_match('/(<\?xml.*<\/sitemapindex>)/s', $cachedContent, $m)) {
            $cleanContent = trim($m[1]);
        } elseif (preg_match('/(<\?xml.*<\/urlset>)/s', $cachedContent, $m)) {
            $cleanContent = trim($m[1]);
        } else {
            $cleanContent = trim($cachedContent);
        }

        ob_end_clean();
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        header('X-Sitemap-Source: cache');
        header('X-Robots-Tag: noindex');

        echo $cleanContent;
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
        exit(0);
    }

    // Serve existing generated sitemap files
    if (empty($_GET['force']) && empty($_GET['generate'])) {
        $page = isset($_GET['p']) ? (int)$_GET['p'] : 0;

        if ($page > 0) {
            $partitionFile = $sitemapDir . "/sitemap-{$page}.xml";
            if (file_exists($partitionFile)) {
                $content = file_get_contents($partitionFile);
                $cleanContent = trim(preg_match('/(<\?xml.*<\/urlset>)/s', $content, $m) ? $m[1] : $content);

                ob_end_clean();
                header('Content-Type: application/xml; charset=utf-8');
                header('X-Sitemap-Source: partition-file');
                header('X-Robots-Tag: noindex');
                echo $cleanContent;
                exit;
            }
        }

        $sitemapIndexFile = $sitemapDir . '/sitemap_index.xml';
        if (file_exists($sitemapIndexFile) && filesize($sitemapIndexFile) > 0) {
            while (ob_get_level()) ob_end_clean();
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: public, max-age=3600');
            header('X-Sitemap-Source: existing-file');
            $content = file_get_contents($sitemapIndexFile);
            echo trim(preg_match('/(<\?xml.*<\/sitemapindex>)/s', $content, $m) ? $m[1] : $content);
            if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
            exit(0);
        }

        $singleSitemapFile = $sitemapDir . '/sitemap.xml';
        if (file_exists($singleSitemapFile) && filesize($singleSitemapFile) > 0) {
            while (ob_get_level()) ob_end_clean();
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: public, max-age=3600');
            header('X-Sitemap-Source: existing-file');
            $content = file_get_contents($singleSitemapFile);
            echo trim(preg_match('/(<\?xml.*<\/urlset>)/s', $content, $m) ? $m[1] : $content);
            if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
            exit(0);
        }

        log_sitemap_generation('WARNING: No existing sitemap file found, generating new one', [
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        ]);
    }
}

// ─── Main Generation ──────────────────────────────────────────────────────────
$baseUrl = base_origin($config);
$now     = date('c');

log_sitemap_generation('Starting sitemap generation with incremental merge', [
    'base_url'     => $baseUrl,
    'is_cli'       => $isCli,
    'current_time' => date('Y-m-d H:i:s'),
]);

// ── Step 1: Baca events lama dari sitemap yang sudah ada ──
$existingEvents = read_existing_sitemap_events($sitemapDir);
log_sitemap_generation('Existing events found in sitemap', ['count' => count($existingEvents)]);

// ── Step 2: Fetch events baru dari API ──
$allApiEvents = [];
$statuses     = ['upcoming', 'live', 'ondemand'];

foreach ($statuses as $status) {
    try {
        $events = fetch_events_from_api($status);
        if (is_array($events) && count($events) > 0) {
            $allApiEvents = array_merge($allApiEvents, $events);
            log_sitemap_generation("Fetched events from API", [
                'status' => $status,
                'count'  => count($events),
            ]);
        }
    } catch (Throwable $e) {
        log_sitemap_generation("Error fetching API events", [
            'status' => $status,
            'error'  => $e->getMessage(),
        ]);
    }
}

$eventsFromApiCount = count($allApiEvents);

// ── Step 3: Merge events lama (existing) + events baru (API) ──
// API events menimpa existing jika ada key yang sama (update status/date)
$statusPriority  = ['live' => 1, 'upcoming' => 2, 'ondemand' => 3];
$mergedEventsMap = $existingEvents;

foreach ($allApiEvents as $event) {
    if (isset($event['key'])) {
        $mergedEventsMap[$event['key']] = $event;
    }
}

// Sort: live dulu, lalu upcoming, lalu ondemand, per-group sort by date
$sortedApiEvents = array_values($mergedEventsMap);
usort($sortedApiEvents, function ($a, $b) use ($statusPriority) {
    $ap = $statusPriority[$a['status'] ?? ''] ?? 99;
    $bp = $statusPriority[$b['status'] ?? ''] ?? 99;
    if ($ap !== $bp) {
        return $ap <=> $bp;
    }
    return strcmp($a['date'] ?? '', $b['date'] ?? '');
});

log_sitemap_generation('Merged & sorted events ready for writing', [
    'total_merged' => count($sortedApiEvents),
    'from_api'     => $eventsFromApiCount,
    'from_existing' => count($existingEvents),
]);

// ── Step 4: Stream-write sitemap files ──
$currentSitemapNumber  = 1;
$urlCountInCurrentFile = 0;
$totalUrlCount         = 0;    // ✅ FIX: nama konsisten $totalUrlCount (bukan $totalUrls)
$sitemaps              = [];   // akan diisi oleh close_sitemap_file()

$currentHandle = open_sitemap_file($currentSitemapNumber, $sitemapDir);

// 4.1 Homepage + player.php di sitemap pertama
fwrite($currentHandle, generate_sitemap_url($baseUrl . '/', $now, 'always', '1.0'));
fwrite($currentHandle, generate_sitemap_url($baseUrl . '/player.php', $now, 'daily', '0.7'));
$urlCountInCurrentFile += 2;
$totalUrlCount         += 2;

// 4.2 API events (+ existing events yang sudah di-merge)
foreach ($sortedApiEvents as $event) {
    $eventKey = $event['key'] ?? '';
    if ($eventKey === '') {
        continue;
    }

    // ✅ FIX: URL berdasarkan tipe event
    $eventType = $event['type'] ?? '';
    if ($eventType === 'keyword_doorway') {
        // ✅ FIX: ltrim/trim untuk mencegah double slash atau spasi
        $url = $baseUrl . '/go/' . ltrim(trim($eventKey), '/');
    } else {
        $url = $baseUrl . '/player.php?event=' . urlencode($eventKey);
    }

    $eventDate            = extract_event_date($event);
    $priorityChangefreq   = get_event_priority_changefreq($event, $eventDate);
    $lastmod              = $eventDate ? $eventDate->format('c') : $now;

    fwrite($currentHandle, generate_sitemap_url(
        $url,
        $lastmod,
        $priorityChangefreq['changefreq'],
        $priorityChangefreq['priority']
    ));

    $urlCountInCurrentFile++;
    $totalUrlCount++;

    if ($urlCountInCurrentFile >= $maxUrlsPerSitemap) {
        close_sitemap_file($currentHandle, $currentSitemapNumber, $sitemapDir, $baseUrl, $now, $sitemaps);
        $currentSitemapNumber++;
        $currentHandle         = open_sitemap_file($currentSitemapNumber, $sitemapDir);
        $urlCountInCurrentFile = 0;
    }
}

// 4.3 Keywords dari DB (stream, skip jika sudah ada di merged events)
log_sitemap_generation('Starting keyword stream from DB...');

// ✅ FIX: Build set dari event keys yang sudah ada (hanya API/existing event keys, bukan keyword slugs)
//         Tujuan: hindari duplikat jika ada keyword slug yang kebetulan sama dengan event key
//         (sangat jarang, tapi lebih aman)
$seenEventKeys = array_fill_keys(array_keys($mergedEventsMap), true);

$keywordGenerator = fetch_keywords_from_db($db_config);
$keywordCount     = 0;

foreach ($keywordGenerator as $kw) {
    // ✅ FIX: slug sudah di-sanitasi di dalam generator (ltrim/trim)
    $slug = $kw['key'] ?? '';
    if ($slug === '') {
        continue;
    }

    // ✅ FIX: Skip hanya jika slug PERSIS sama dengan event key (bukan hanya ada di map)
    //         Ini mencegah duplikat jika keyword doorway punya slug yang sama dengan event key
    if (isset($seenEventKeys[$slug])) {
        continue;
    }

    // ✅ FIX: URL keyword doorway menggunakan /go/slug
    $url = $baseUrl . '/go/' . $slug;

    fwrite($currentHandle, generate_sitemap_url($url, $now, 'weekly', '0.6'));

    $urlCountInCurrentFile++;
    $totalUrlCount++;
    $keywordCount++;

    if ($urlCountInCurrentFile >= $maxUrlsPerSitemap) {
        close_sitemap_file($currentHandle, $currentSitemapNumber, $sitemapDir, $baseUrl, $now, $sitemaps);
        $currentSitemapNumber++;
        $currentHandle         = open_sitemap_file($currentSitemapNumber, $sitemapDir);
        $urlCountInCurrentFile = 0;
    }

    if ($keywordCount % 50000 === 0) {
        log_sitemap_generation("Keyword stream progress", [
            'keywords_written' => $keywordCount,
            'total_urls'       => $totalUrlCount,
        ]);
    }
}

log_sitemap_generation("Keywords written to sitemap", ['count' => $keywordCount]);

// ── Step 5: Tutup file terakhir ──
if ($urlCountInCurrentFile > 0) {
    close_sitemap_file($currentHandle, $currentSitemapNumber, $sitemapDir, $baseUrl, $now, $sitemaps);
} else {
    // File terakhir kosong, hapus
    fclose($currentHandle);
    @unlink($sitemapDir . "/sitemap-{$currentSitemapNumber}.xml");
}
log_sitemap_generation('All sitemap files written', [
    'sitemap_count' => count($sitemaps),
    'total_urls'    => $totalUrlCount,
]);

// ── Step 6: Generate Sitemap Index atau Single Sitemap ──
$sitemapIndexFile = $sitemapDir . '/sitemap_index.xml';
$finalSitemapFile = $sitemapDir . '/sitemap.xml';

if (count($sitemaps) > 1) {
    // Generate Index
    $indexContent = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $indexContent .= "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach ($sitemaps as $s) {
        $indexContent .= generate_sitemap_index_entry($s['url'], $s['lastmod']);
    }
    $indexContent .= "</sitemapindex>";

    file_put_contents($sitemapIndexFile, $indexContent);
    @unlink($finalSitemapFile); // Hapus single sitemap jika ada

    log_sitemap_generation("Sitemap index generated", ['file' => $sitemapIndexFile]);
} elseif (count($sitemaps) === 1) {
    // Hanya ada 1 file, copy sitemap-1.xml ke sitemap.xml
    $firstFile = $sitemapDir . '/sitemap-1.xml';
    if (file_exists($firstFile)) {
        copy($firstFile, $finalSitemapFile);
        @unlink($sitemapIndexFile); // Hapus index jika ada
        log_sitemap_generation("Single sitemap generated", ['file' => $finalSitemapFile]);
    }
}

// ── Step 7: Ping Search Engines ──
if ($totalUrlCount > 0) {
    $mainSitemapUrl = (count($sitemaps) > 1)
        ? $baseUrl . '/sitemaps/sitemap_index.xml'
        : $baseUrl . '/sitemaps/sitemap.xml';

    ping_search_engines($mainSitemapUrl);
}

// ── Step 8: Final Output ──
log_sitemap_generation('Sitemap generation completed successfully', [
    'total_urls' => $totalUrlCount,
    'files'      => count($sitemaps)
]);

if (!$isCli) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/xml; charset=utf-8');

    $outputFile = (count($sitemaps) > 1) ? $sitemapIndexFile : $finalSitemapFile;
    if (file_exists($outputFile)) {
        readfile($outputFile);
    } else {
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<error>Sitemap file not found</error>";
    }
} else {
    echo "Sitemap generation completed. Total URLs: {$totalUrlCount}, Total Files: " . count($sitemaps) . "\n";
}
