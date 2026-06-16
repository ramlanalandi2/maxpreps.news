<?php
declare(strict_types=1);

/**
 * ✅ PERFORMANCE OPTIMIZATION: Caching System
 * 
 * Caching untuk mengurangi API calls dan meningkatkan performa
 * - File-based caching untuk API responses
 * - TTL (Time To Live) untuk auto-expire
 * - Automatic cleanup untuk cache lama
 */

// Cache directory
$cacheDir = __DIR__ . '/../cache';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
    // ✅ PERFORMANCE: Create .htaccess untuk protect cache directory
    $htaccessFile = $cacheDir . '/.htaccess';
    if (!file_exists($htaccessFile)) {
        @file_put_contents($htaccessFile, "Order deny,allow\nDeny from all\n");
    }
}

// Default TTL: 5 menit untuk event data, 1 jam untuk static data
define('CACHE_TTL_EVENT', 300);      // 5 minutes
define('CACHE_TTL_STATIC', 3600);    // 1 hour
define('CACHE_TTL_SHORT', 60);       // 1 minute

/**
 * Get cache key dari URL atau identifier
 */
function get_cache_key(string $identifier): string {
    return md5($identifier);
}

/**
 * Get cache file path
 */
function get_cache_path(string $key): string {
    global $cacheDir;
    $subDir = substr($key, 0, 2);
    $subDirPath = $cacheDir . '/' . $subDir;
    if (!is_dir($subDirPath)) {
        @mkdir($subDirPath, 0755, true);
    }
    return $subDirPath . '/' . $key . '.json';
}

/**
 * Get cached data
 */
function cache_get(string $key, int $ttl = CACHE_TTL_EVENT): ?array {
    $cacheFile = get_cache_path($key);
    
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    // Check if cache expired
    $fileTime = filemtime($cacheFile);
    if ((time() - $fileTime) > $ttl) {
        @unlink($cacheFile);
        return null;
    }
    
    $content = @file_get_contents($cacheFile);
    if ($content === false) {
        return null;
    }
    
    $data = @json_decode($content, true);
    if (!is_array($data)) {
        return null;
    }
    
    return $data;
}

/**
 * Set cache data
 */
function cache_set(string $key, array $data, int $ttl = CACHE_TTL_EVENT): bool {
    $cacheFile = get_cache_path($key);
    
    $cacheData = [
        'data' => $data,
        'cached_at' => time(),
        'ttl' => $ttl,
    ];
    
    $result = @file_put_contents($cacheFile, json_encode($cacheData, JSON_UNESCAPED_SLASHES), LOCK_EX);
    
    if ($result !== false && (time() % 100 === 0)) {
        // Periodic cleanup (1% of requests)
        cache_cleanup();
    }
    
    return $result !== false;
}

/**
 * Delete cache
 */
function cache_delete(string $key): bool {
    $cacheFile = get_cache_path($key);
    if (file_exists($cacheFile)) {
        return @unlink($cacheFile);
    }
    return true;
}

/**
 * Cleanup expired cache files
 */
function cache_cleanup(): void {
    global $cacheDir;
    
    if (!is_dir($cacheDir)) {
        return;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    $now = time();
    $cleaned = 0;
    $maxClean = 100; // Limit cleanup per run
    
    foreach ($iterator as $file) {
        if ($cleaned >= $maxClean) {
            break;
        }
        
        if ($file->isFile() && $file->getExtension() === 'json') {
            $fileTime = $file->getMTime();
            // Delete if older than 1 hour (expired cache)
            if (($now - $fileTime) > 3600) {
                @unlink($file->getPathname());
                $cleaned++;
            }
        }
    }
}

/**
 * Clear all cache
 */
function cache_clear_all(): void {
    global $cacheDir;
    
    if (!is_dir($cacheDir)) {
        return;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'json') {
            @unlink($file->getPathname());
        } elseif ($file->isDir()) {
            @rmdir($file->getPathname());
        }
    }
}
