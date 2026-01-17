<?php
declare(strict_types=1);

namespace TikTokApi\Classes;

/**
 * キャッシュ管理クラス
 */
class Cache {
    private string $directory;
    private int $ttl;
    private bool $enabled;
    
    public function __construct(array $config) {
        $this->enabled = $config['cache']['enabled'];
        $this->directory = $config['cache']['directory'];
        $this->ttl = $config['cache']['ttl'];
    }
    
    /**
     * キャッシュキーからファイルパスを生成
     */
    private function getFilePath(string $key): string {
        return $this->directory . hash('sha256', $key) . '.json';
    }
    
    /**
     * キャッシュからデータを取得
     */
    public function get(string $key): ?array {
        if (!$this->enabled) {
            return null;
        }
        
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $mtime = filemtime($file);
        if ($mtime === false || (time() - $mtime) > $this->ttl) {
            @unlink($file);
            return null;
        }
        
        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }
        
        $data = json_decode($content, true);
        return is_array($data) ? $data : null;
    }
    
    /**
     * キャッシュにデータを保存
     */
    public function set(string $key, array $data): bool {
        if (!$this->enabled) {
            return false;
        }
        
        if (!is_dir($this->directory)) {
            if (!@mkdir($this->directory, 0750, true)) {
                return false;
            }
        }
        
        $file = $this->getFilePath($key);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        if ($json === false) {
            return false;
        }
        
        return file_put_contents($file, $json, LOCK_EX) !== false;
    }
    
    /**
     * キャッシュを削除
     */
    public function delete(string $key): bool {
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            return @unlink($file);
        }
        
        return true;
    }
    
    /**
     * 期限切れキャッシュを一括削除
     */
    public function cleanup(): int {
        if (!is_dir($this->directory)) {
            return 0;
        }
        
        $count = 0;
        $files = glob($this->directory . '*.json');
        
        if ($files === false) {
            return 0;
        }
        
        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime !== false && (time() - $mtime) > $this->ttl) {
                if (@unlink($file)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
}
