<?php
declare(strict_types=1);

/**
 * Cloaker Class
 * Optimized for boot traffic tools while blocking essential ad platform reviewers.
 */
class Cloaker
{
    /**
     * ESSENTIAL Blacklist (Reviewers & Crawlers only)
     * We only block things that confirmed to be ad platform checkers.
     */
    private static array $reviewerHashes = [
        'google-publisher-plugin',
        'bingbot', 'bingpreview', 'msnbot', 'slurp', 'duckduckbot', 'baiduspider',
        'yandex', 'facebot', 'facebookexternalhit', 'facebookcatalog', 'twitterbot',
        'linkedinbot', 'whatsapp', 'telegrambot', 'applebot', 'ia_archiver',
        'adsbridge', 'voluum', 'redtrack', 'binom', 'keitaro', 'funnelish',
        'adsterra', 'propellerads', 'mgid', 'taboola', 'outbrain', 'revcontent',
        'admitad'
    ];

    /**
     * Check if the current request is likely from a REVIWER
     */
    public static function isReviewer(): bool
    {
        $userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        // No UA or empty UA is suspicious for a money-page, but for boost tools we might allow it.
        // Let's only block if it matches a known reviewer hash.
        if (empty($userAgent)) {
            return false;
        }

        foreach (self::$reviewerHashes as $hash) {
            if (strpos($userAgent, $hash) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the real client IP
     */
    public static function getRealIp(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Final verdict: true if request should go to the MONEY page
     */
    public static function isHuman(): bool
    {
        // 1. Secret bypass parameter (backend support)
        if (isset($_GET['bypass']) && $_GET['bypass'] === 'magnus') {
            return true;
        }

        // 2. Identify known reviewers
        if (self::isReviewer()) {
            return false;
        }

        // 3. Default to HUMAN (Loloskan semua untuk tool boost traffic)
        return true;
    }
}
