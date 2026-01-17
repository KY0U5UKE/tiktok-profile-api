<?php
declare(strict_types=1);

/**
 * TikTok Profile API 設定ファイル
 */

return [
    // キャッシュ設定
    'cache' => [
        'enabled' => true,
        'directory' => sys_get_temp_dir() . '/tiktok_cache/',
        'ttl' => 300, // 5分
    ],
    
    // レート制限設定
    'rate_limit' => [
        'enabled' => true,
        'max_requests' => 30,
        'window' => 60, // 秒
        'directory' => sys_get_temp_dir() . '/tiktok_ratelimit/',
    ],
    
    // cURL設定
    'curl' => [
        'timeout' => 30,
        'connect_timeout' => 10,
        'max_redirects' => 3,
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ],
    
    // パース設定
    'parser' => [
        'json_max_length' => 100000,
    ],
    
    // ログ設定
    'log' => [
        'prefix' => '[TikTok Profile API] ',
        'max_length' => 500,
    ],
];
