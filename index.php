<?php
declare(strict_types=1);

/**
 * TikTok Profile API
 * エントリーポイント
 */

// オートローダー
require_once __DIR__ . '/src/autoload.php';

// 設定読み込み
$config = require __DIR__ . '/config/settings.php';

// クラスをインポート
use TikTokApi\Classes\Response;
use TikTokApi\Classes\Cache;
use TikTokApi\Classes\RateLimiter;
use TikTokApi\Classes\TikTokClient;

// ヘッダー設定
Response::setHeaders();

// CORS preflight
Response::handlePreflight();

// GETメソッドのみ
Response::requireGet();

// レート制限チェック
$rateLimiter = new RateLimiter($config);
if (!$rateLimiter->check()) {
    Response::rateLimitExceeded();
}

// パラメータチェック
if (!isset($_GET['username']) || !is_string($_GET['username']) || trim($_GET['username']) === '') {
    Response::error('ユーザーネームのパラメータがありません');
}

// TikTok IDを抽出
$tiktokId = extractTiktokId($_GET['username']);

if ($tiktokId === null) {
    Response::error('入力されたURLからIDを取得できませんでした');
}

// バリデーション
if (!validateUsername($tiktokId)) {
    Response::error('ユーザーネームの形式が正しくありません（英数字、アンダースコア、ドットのみ、2-24文字）');
}

// キャッシュチェック
$cache = new Cache($config);
$cachedData = $cache->get($tiktokId);
if ($cachedData !== null) {
    Response::success($cachedData, true);
}

// TikTokからデータ取得
try {
    $client = new TikTokClient($config);
    
    $html = $client->fetchProfile($tiktokId);
    $userData = $client->parseUserData($html);
    $stats = $client->parseStats($html, $userData);
    $profileData = $client->formatProfileData($userData, $stats);
    
    // キャッシュ保存
    $cache->set($tiktokId, $profileData);
    
    Response::success($profileData);
    
} catch (RuntimeException $e) {
    Response::error($e->getMessage(), $e->getCode() ?: 500);
}
