<?php

declare(strict_types=1);

// ✅ Define BASE_DIR for local file paths
if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(__DIR__));
}

/**
 * Get the base origin URL of the website
 * 
 * @return string Base URL with scheme and host
 */
if (!function_exists('base_origin')) {
    function base_origin(): string
    {
        $scheme = 'http';
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        }
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}

// ✅ Define SITE_PATH for assets & links (works in root or subdirectory)
if (!defined('SITE_PATH')) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    // Standardize script name to have leading slash (handles some CGI environments)
    if ($scriptName !== '' && strpos($scriptName, '/') !== 0) {
        $scriptName = '/' . $scriptName;
    }
    $sitePath = rtrim(dirname($scriptName), '/\\');
    // Ensure SITE_PATH is an absolute web path (starts with /)
    define('SITE_PATH', ($sitePath === '' || $sitePath === '/' || $sitePath === '.' || $sitePath === DIRECTORY_SEPARATOR ? '/' : $sitePath . '/'));
}

/**
 * Get the modification time of an asset for versioning
 * 
 * @param string $relativePath Path relative to BASE_DIR
 * @return string Unix timestamp or current time if file missing
 */
function get_asset_version(string $relativePath): string
{
    $fullPath = BASE_DIR . '/' . ltrim($relativePath, '/');
    if (file_exists($fullPath)) {
        return (string)filemtime($fullPath);
    }
    return (string)time();
}

/**
 * Extract team names from an NFHS event object.
 * 
 * @param array $ev The event data array.
 * @return array ['home' => string, 'away' => string]
 */
/**
 * Extract team names from an NFHS event object.
 * 
 * @param array $event The event data array.
 * @param array $details Optional details array for fallback.
 * @return array ['home' => string, 'away' => string]
 */
function extract_team_names(array $event, array $details = []): array
{
    $meta = $event['metadata'] ?? [];

    // Priority: details > metadata > event nested > event direct > title parsing > details title parsing

    // Home team
    $home = null;
    if (!empty($details['home_team'])) {
        $home = $details['home_team'];
    } elseif (!empty($meta['home_team_name'])) {
        $home = $meta['home_team_name'];
    } elseif (!empty($meta['home_team'])) {
        $home = $meta['home_team'];
    } elseif (!empty($event['homeTeam']['name'])) {
        $home = $event['homeTeam']['name'];
    } elseif (!empty($event['second_title'])) {
        $home = $event['second_title'];
    } elseif (!empty($event['home'])) {
        $home = $event['home'];
    }

    // Away team
    $away = null;
    if (!empty($details['away_team'])) {
        $away = $details['away_team'];
    } elseif (!empty($meta['away_team_name'])) {
        $away = $meta['away_team_name'];
    } elseif (!empty($meta['away_team'])) {
        $away = $meta['away_team'];
    } elseif (!empty($event['awayTeam']['name'])) {
        $away = $event['awayTeam']['name'];
    } elseif (!empty($event['first_title'])) {
        $away = $event['first_title'];
    } elseif (!empty($event['away'])) {
        $away = $event['away'];
    }

    // Parse from event title if team names still missing
    $title = $event['title'] ?? ($event['name'] ?? ($meta['title'] ?? ''));
    if ((empty($home) || empty($away)) && $title && stripos($title, ' vs ') !== false) {
        $parts = preg_split('/\s+vs\s+/i', $title);
        if (count($parts) >= 2) {
            $home = $home ?: trim($parts[0]);
            $away = $away ?: trim($parts[1]);
        }
    }

    // Parse from details title if team names still missing
    if ((empty($home) || empty($away)) && !empty($details['title'])) {
        $detailsTitle = $details['title'];
        // Remove common suffixes like "| Live & On Demand", "- Boys Varsity Basketball", etc.
        $detailsTitle = preg_replace('/\s*\|\s*Live.*$/i', '', $detailsTitle);
        $detailsTitle = preg_replace('/\s*-\s*[^-]+$/i', '', $detailsTitle);

        if (stripos($detailsTitle, ' vs ') !== false) {
            $parts = preg_split('/\s+vs\s+/i', $detailsTitle, 2);
            if (count($parts) >= 2) {
                $home = $home ?: trim($parts[0]);
                $away = $away ?: trim($parts[1]);
            }
        }
    }

    return [
        'home' => $home ?: '',
        'away' => $away ?: '',
    ];
}

/**
 * Resolve an image URL to be absolute or correctly relative to SITE_PATH.
 * 
 * @param string|null $url The raw image URL.
 * @param bool $forceAbsolute Whether to return a full URL with domain (e.g. for og:image).
 * @return string The resolved image URL.
 */
function resolve_image_url(?string $url, bool $forceAbsolute = false): string
{
    $config = @require dirname(__DIR__) . '/config.php';
    $default = $config['default_thumbnail'] ?? $config['default_share_image'] ?? 'https://maxpreps.news/assets/images/default-sports.png';

    if (!$url || trim($url) === '') {
        return $default;
    }

    $trimmed = trim($url);

    // ✅ CDN Normalization (from normalize_cdn_image)
    // Force i0.wp.com proxy and HTTPS for NFHS images
    $httpPrefix = 'http://social.nfhsnetwork.com/';
    $httpsPrefix = 'https://social.nfhsnetwork.com/';
    if (strpos($trimmed, $httpsPrefix) === 0) {
        $trimmed = 'https://i0.wp.com/social.nfhsnetwork.com/' . substr($trimmed, strlen($httpsPrefix));
        return $trimmed;
    }
    if (strpos($trimmed, $httpPrefix) === 0) {
        $trimmed = 'https://i0.wp.com/social.nfhsnetwork.com/' . substr($trimmed, strlen($httpPrefix));
        return $trimmed;
    }

    // External URL (starts with http:// or https://)
    if (preg_match('/^https?:\/\//i', $trimmed)) {
        // Force HTTPS for social media compatibility (Facebook og:image requires HTTPS)
        if (strpos($trimmed, 'http://') === 0) {
            return str_replace('http://', 'https://', $trimmed);
        }
        return $trimmed;
    }

    // Relative URL - strip leading slash and prepend SITE_PATH
    $path = ltrim($trimmed, '/\\');

    // Check if it's already starting with SITE_PATH (to avoid double prefixing)
    if (SITE_PATH !== '/' && strpos($trimmed, SITE_PATH) === 0) {
        $relativePath = $trimmed;
    } else {
        $relativePath = SITE_PATH . $path;
    }

    if ($forceAbsolute) {
        $origin = rtrim(base_origin(), '/');
        return $origin . '/' . ltrim($relativePath, '/');
    }

    return $relativePath;
}

/**
 * Legacy compatibility for normalize_cdn_image. Now handled by resolve_image_url.
 * 
 * @param string|null $url The raw image URL.
 * @return string|null The resolved image URL.
 */
function normalize_cdn_image(?string $url): ?string
{
    return resolve_image_url($url);
}
