<?php
declare(strict_types=1);

namespace TikTokApi\Classes;

/**
 * レート制限クラス
 */
class RateLimiter {
    private string $directory;
    private int $maxRequests;
    private int $window;
    private bool $enabled;
    
    public function __construct(array $config) {
        $this->enabled = $config['rate_limit']['enabled'];
        $this->directory = $config['rate_limit']['directory'];
        $this->maxRequests = $config['rate_limit']['max_requests'];
        $this->window = $config['rate_limit']['window'];
    }
    
    /**
     * IPアドレスを取得
     */
    private function getClientIp(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * レート制限キーを生成
     */
    private function getKey(string $ip): string {
        $timeSlot = floor(time() / $this->window);
        return hash('sha256', $ip . ':' . $timeSlot);
    }
    
    /**
     * ファイルパスを取得
     */
    private function getFilePath(string $key): string {
        return $this->directory . $key;
    }
    
    /**
     * リクエストが許可されるかチェック
     */
    public function check(): bool {
        if (!$this->enabled) {
            return true;
        }
        
        $ip = $this->getClientIp();
        $key = $this->getKey($ip);
        $file = $this->getFilePath($key);
        
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0750, true);
        }
        
        $count = 0;
        if (file_exists($file)) {
            $count = (int)file_get_contents($file);
        }
        
        if ($count >= $this->maxRequests) {
            return false;
        }
        
        file_put_contents($file, (string)($count + 1), LOCK_EX);
        
        return true;
    }
    
    /**
     * 残りリクエスト数を取得
     */
    public function getRemaining(): int {
        $ip = $this->getClientIp();
        $key = $this->getKey($ip);
        $file = $this->getFilePath($key);
        
        $count = 0;
        if (file_exists($file)) {
            $count = (int)file_get_contents($file);
        }
        
        return max(0, $this->maxRequests - $count);
    }
    
    /**
     * 古いレート制限ファイルを削除
     */
    public function cleanup(): int {
        if (!is_dir($this->directory)) {
            return 0;
        }
        
        $count = 0;
        $files = glob($this->directory . '*');
        
        if ($files === false) {
            return 0;
        }
        
        $expireTime = time() - ($this->window * 2);
        
        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime !== false && $mtime < $expireTime) {
                if (@unlink($file)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
}
