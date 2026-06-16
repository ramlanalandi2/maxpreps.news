<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| AUTO INDEXING ENGINE (NO CRON, SHARED HOSTING SAFE)
|--------------------------------------------------------------------------
| - Auto generate IndexNow key if not exists
| - Auto create verification .txt file
| - Auto detect new article (append system)
| - Lock protected
| - Silent mode (no output)
|--------------------------------------------------------------------------
*/

function auto_index_if_needed(): void
{
    $baseUrl = 'https://maxpreps.news';
    $host = parse_url($baseUrl, PHP_URL_HOST);

    $newsFile = __DIR__ . '/data/news.json';
    $lastKeyFile = __DIR__ . '/last_indexed_key.txt';
    $lockFile = __DIR__ . '/index.lock';
    $indexNowKeyFile = __DIR__ . '/indexnow.key';
    $envFile = __DIR__ . '/.env';

    // ===============================
    // LOAD ENV
    // ===============================
    $config = [];
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            $config[trim($name)] = trim($value);
        }
    }

    // ===============================
    // LOCK SYSTEM
    // ===============================
    if (file_exists($lockFile)) {
        return;
    }
    file_put_contents($lockFile, (string)time());

    try {

        // ===============================
        // AUTO GENERATE INDEXNOW KEY
        // ===============================
        if (!file_exists($indexNowKeyFile)) {

            $generatedKey = bin2hex(random_bytes(16));
            file_put_contents($indexNowKeyFile, $generatedKey);

            // create verification file
            file_put_contents(__DIR__ . "/{$generatedKey}.txt", $generatedKey);
        }

        $indexNowKey = trim(file_get_contents($indexNowKeyFile));

        // ===============================
        // VALIDATE NEWS FILE
        // ===============================
        if (!file_exists($newsFile)) {
            return;
        }

        $data = json_decode(file_get_contents($newsFile), true);
        if (empty($data['items'])) {
            return;
        }

        // Append system → ambil terakhir
        $latestItem = end($data['items']);
        if (empty($latestItem['key'])) {
            return;
        }

        $currentKey = (string)$latestItem['key'];

        $lastIndexedKey = file_exists($lastKeyFile)
            ? trim(file_get_contents($lastKeyFile))
            : '';

        // Tidak ada artikel baru
        if ($currentKey === $lastIndexedKey) {
            return;
        }

        // ===============================
        // BUILD ARTICLE URL
        // ===============================
        $title = $latestItem['headline'] ?? $latestItem['title'] ?? 'news';
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');

        $articleUrl = "{$baseUrl}/news/{$slug}-{$currentKey}";

        // ===============================
        // SUBMIT TO INDEXNOW
        // ===============================
        $payload = json_encode([
            'host' => $host,
            'key' => $indexNowKey,
            'keyLocation' => "https://{$host}/{$indexNowKey}.txt",
            'urlList' => [$articleUrl]
        ]);

        $ch = curl_init('https://api.indexnow.org/indexnow');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // ===============================
        // SUBMIT TO CLOUDFLARE CRAWL
        // ===============================
        $cfAccountId = $config['CF_ACCOUNT_ID'] ?? null;
        $cfApiToken = $config['CF_API_TOKEN'] ?? null;

        if ($cfAccountId && $cfApiToken) {
            $cfPayload = json_encode([
                'url' => $articleUrl,
                'render' => true // Ensure JS rendering for modern SEO
            ]);

            $cfCh = curl_init("https://api.cloudflare.com/client/v4/accounts/{$cfAccountId}/browser-rendering/crawl");
            curl_setopt_array($cfCh, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $cfPayload,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer {$cfApiToken}",
                    "Content-Type: application/json"
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);
            
            $cfResponse = curl_exec($cfCh);
            $cfHttpCode = curl_getinfo($cfCh, CURLINFO_HTTP_CODE);
            curl_close($cfCh);

            // Simple logging for audit
            file_put_contents(__DIR__ . '/cloudflare_crawl.log', date('[Y-m-d H:i:s] ') . "Job for {$articleUrl} - Status: {$cfHttpCode} - Response: {$cfResponse}\n", FILE_APPEND);
        }

        // ===============================
        // SAVE SUCCESS
        // ===============================
        if ($httpCode >= 200 && $httpCode < 300) {
            file_put_contents($lastKeyFile, $currentKey);
        }

    } catch (Throwable $e) {
        // Silent fail (tidak ganggu homepage)
    } finally {
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }
}