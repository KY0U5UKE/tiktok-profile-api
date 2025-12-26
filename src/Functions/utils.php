<?php
declare(strict_types=1);

/**
 * ユーティリティ関数
 */

/**
 * 設定値を読みやすいテキストに変換
 */
function translateSetting(mixed $value): string {
    if ($value === null || $value === '' || !is_scalar($value)) {
        return '不明';
    }
    
    $settings = [
        0 => '全員許可',
        1 => 'フォロワーのみ',
        2 => 'フレンドのみ',
        3 => '非公開'
    ];
    
    $intValue = (int)$value;
    return $settings[$intValue] ?? "設定値: {$intValue}";
}

/**
 * 数値をフォーマット（K, M表記）
 */
function formatNumber(mixed $num): string {
    if (!is_numeric($num)) {
        return '0';
    }
    
    $num = (float)$num;
    
    if ($num >= 1000000) {
        return number_format($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return number_format($num / 1000, 1) . 'K';
    }
    
    return number_format($num);
}

/**
 * ユーザーネームのバリデーション
 */
function validateUsername(string $username): bool {
    return (bool)preg_match('/^[a-zA-Z0-9_.]{2,24}$/', $username);
}

/**
 * URLをサニタイズ
 */
function sanitizeUrl(string $url): string {
    if (empty($url)) {
        return '';
    }
    
    $url = str_replace(['\\', "\0"], '', $url);
    
    // TikTok CDN URLのみ許可
    $patterns = [
        '#^https?://[a-z0-9.-]+\.tiktokcdn(-[a-z]+)?\.com/#i',
        '#^https?://p[0-9]+-sign[a-z-]*\.tiktokcdn[a-z-]*\.com/#i',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url)) {
            return $url;
        }
    }
    
    return '';
}

/**
 * ログ出力（サニタイズ済み）
 */
function safeLog(string $message, array $config): void {
    $sanitized = preg_replace('/[\r\n\x00]/', ' ', $message);
    $sanitized = mb_substr($sanitized, 0, $config['log']['max_length']);
    error_log($config['log']['prefix'] . $sanitized);
}

/**
 * 配列から文字列を安全に取得
 */
function getString(array $arr, string $key, string $default = ''): string {
    return isset($arr[$key]) && is_string($arr[$key]) ? $arr[$key] : $default;
}

/**
 * 配列から整数を安全に取得
 */
function getInt(array $arr, string $key, int $default = 0): int {
    return isset($arr[$key]) && is_numeric($arr[$key]) ? (int)$arr[$key] : $default;
}

/**
 * 配列から真偽値を安全に取得
 */
function getBool(array $arr, string $key, bool $default = false): bool {
    return isset($arr[$key]) ? (bool)$arr[$key] : $default;
}

/**
 * ユーザー入力からTikTok IDを抽出
 */
function extractTiktokId(string $input): ?string {
    $input = trim($input);
    
    // URLからIDを抽出
    if (str_contains($input, 'tiktok.com/')) {
        if (preg_match('/tiktok\.com\/@([a-zA-Z0-9_.]+)/i', $input, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    // @プレフィックスを除去
    if (str_starts_with($input, '@')) {
        $input = substr($input, 1);
    }
    
    return $input;
}
