<?php
declare(strict_types=1);

/**
 * シンプルなオートローダー
 */

spl_autoload_register(function (string $class): void {
    // 名前空間プレフィックス
    $prefix = 'TikTokApi\\Classes\\';
    
    // クラスディレクトリ
    $baseDir = __DIR__ . '/Classes/';
    
    // プレフィックスが一致するか確認
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // クラス名からファイルパスを生成
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// 関数ファイルを読み込み
require_once __DIR__ . '/Functions/utils.php';
