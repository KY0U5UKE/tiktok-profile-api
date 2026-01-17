<?php
declare(strict_types=1);

namespace TikTokApi\Classes;

/**
 * HTTPレスポンスクラス
 */
class Response {
    /**
     * JSONレスポンス用ヘッダーを設定
     */
    public static function setHeaders(): void {
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Cache-Control: private, max-age=300');
    }
    
    /**
     * CORS preflightリクエストを処理
     */
    public static function handlePreflight(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
    
    /**
     * HTTPメソッドをチェック
     */
    public static function requireGet(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            self::error('GETメソッドのみ対応しています', 405);
        }
    }
    
    /**
     * エラーレスポンスを返す
     */
    public static function error(string $message, int $code = 400): never {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => time()
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
    
    /**
     * 成功レスポンスを返す
     */
    public static function success(array $data, bool $fromCache = false): never {
        $response = [
            'success' => true,
            'data' => $data,
            'timestamp' => time()
        ];
        
        if ($fromCache) {
            $response['cached'] = true;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
    
    /**
     * レート制限エラーを返す
     */
    public static function rateLimitExceeded(): never {
        header('Retry-After: 60');
        self::error('リクエスト制限に達しました。しばらく待ってから再試行してください。', 429);
    }
}
