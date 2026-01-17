<?php
declare(strict_types=1);

namespace TikTokApi\Classes;

use RuntimeException;

/**
 * TikTok Profile APIクライアント
 */
class TikTokClient {
    private array $config;
    
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    /**
     * ユーザープロフィールページのHTMLを取得
     */
    public function fetchProfile(string $username): string {
        $url = "https://www.tiktok.com/@" . rawurlencode($username);
        $curlConfig = $this->config['curl'];
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $curlConfig['timeout'],
            CURLOPT_CONNECTTIMEOUT => $curlConfig['connect_timeout'],
            CURLOPT_USERAGENT => $curlConfig['user_agent'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => $curlConfig['max_redirects'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: ja,en;q=0.9',
                'Cache-Control: no-cache',
            ],
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($html === false || $curlErrno !== 0) {
            safeLog("Fetch error: errno={$curlErrno}, error={$curlError}", $this->config);
            throw new RuntimeException('プロフィールの取得中にネットワークエラーが発生しました (cURL error: ' . $curlErrno . ')', 500);
        }

        if ($httpCode === 404) {
            throw new RuntimeException('ユーザーが存在しません', 404);
        }

        if ($httpCode !== 200) {
            safeLog("HTTP error: {$httpCode}", $this->config);
            throw new RuntimeException('プロフィールの取得中にエラーが発生しました (HTTP status: ' . $httpCode . ')', 500);
        }
        
        return $html;
    }
    
    /**
     * HTMLからJSONオブジェクトを抽出
     */
    public function extractJsonObject(string $html, string $startPattern): ?array {
        $startIndex = strpos($html, $startPattern);
        if ($startIndex === false) {
            return null;
        }

        $braceCount = 0;
        $jsonStart = $startIndex;
        $length = strlen($html);
        $maxLength = min($length, $startIndex + $this->config['parser']['json_max_length']);

        // 元のコードと同じシンプルなアルゴリズムを使用
        for ($i = $startIndex; $i < $maxLength; $i++) {
            if ($html[$i] === '{') {
                if ($braceCount === 0) {
                    $jsonStart = $i;
                }
                $braceCount++;
            } elseif ($html[$i] === '}') {
                $braceCount--;
                if ($braceCount === 0) {
                    $jsonStr = substr($html, $jsonStart, $i - $jsonStart + 1);
                    $decoded = json_decode($jsonStr, true);
                    return is_array($decoded) ? $decoded : null;
                }
            }
        }

        return null;
    }
    
    /**
     * HTMLからユーザーデータを抽出
     */
    public function parseUserData(string $html): array {
        $userData = $this->extractJsonObject($html, '"UserModule":{');

        if ($userData === null) {
            // 元のコードと同じ柔軟なパターンを使用
            $pattern = '/{"id":"([^"]+)","shortId":"[^"]*","uniqueId":"([^"]+)","nickname":"([^"]+)"/';
            if (!preg_match($pattern, $html, $matches)) {
                safeLog("UserModule pattern not found in HTML", $this->config);
                throw new RuntimeException('ユーザーデータの抽出に失敗しました。ユーザーが存在しないか、TikTokのHTML構造が変更された可能性があります', 500);
            }

            $userData = $this->extractJsonObject($html, '{"id":"' . $matches[1]);

            if ($userData === null) {
                safeLog("Failed to parse JSON for user ID: {$matches[1]}", $this->config);
                throw new RuntimeException('ユーザーデータの解析に失敗しました。TikTokのHTML構造が変更された可能性があります', 500);
            }
        }

        return $userData;
    }
    
    /**
     * HTMLから統計データを抽出
     */
    public function parseStats(string $html, array $userData): array {
        // 元のコードと同じロジックを使用
        $stats = [];

        $statsStartIndex = strpos($html, '"stats":');
        if ($statsStartIndex !== false) {
            $statsEndIndex = $statsStartIndex + 8; // "stats":の後から開始
            $braceCount = 1;

            for ($i = $statsEndIndex + 1; $i < strlen($html) && $braceCount > 0; $i++) {
                if ($html[$i] === '{') {
                    $braceCount++;
                } elseif ($html[$i] === '}') {
                    $braceCount--;
                }

                if ($braceCount === 0) {
                    $statsEndIndex = $i + 1;
                    break;
                }
            }

            $statsJson = substr($html, $statsStartIndex + 8, $statsEndIndex - ($statsStartIndex + 8));
            $stats = json_decode($statsJson, true);

            if ($stats !== null && is_array($stats)) {
                return $stats;
            }
        }

        // フォールバック: userDataから取得
        if (isset($userData['stats']) && is_array($userData['stats'])) {
            return $userData['stats'];
        }

        return [];
    }
    
    /**
     * プロフィールデータを整形
     */
    public function formatProfileData(array $userData, array $stats): array {
        return [
            'profile' => [
                'uid' => getString($userData, 'id'),
                'uniqueid' => getString($userData, 'uniqueId'),
                'nickname' => getString($userData, 'nickname'),
                'signature' => getString($userData, 'signature'),
                'avatar' => sanitizeUrl(getString($userData, 'avatarLarger')),
                'avatar-medium' => sanitizeUrl(getString($userData, 'avatarMedium')),
                'avatar-thumb' => sanitizeUrl(getString($userData, 'avatarThumb')),
                'profile-url' => "https://www.tiktok.com/@" . getString($userData, 'uniqueId'),
                'verified' => getBool($userData, 'verified'),
                'private-account' => getBool($userData, 'privateAccount'),
            ],
            
            'stats' => [
                'follower-count' => getInt($stats, 'followerCount'),
                'following-count' => getInt($stats, 'followingCount'),
                'video-count' => getInt($stats, 'videoCount'),
                'heart-count' => getInt($stats, 'heartCount'),
                'friend-count' => getInt($stats, 'friendCount'),
                'formatted' => [
                    'follower-count' => formatNumber($stats['followerCount'] ?? 0),
                    'following-count' => formatNumber($stats['followingCount'] ?? 0),
                    'video-count' => formatNumber($stats['videoCount'] ?? 0),
                    'heart-count' => formatNumber($stats['heartCount'] ?? 0),
                    'friend-count' => formatNumber($stats['friendCount'] ?? 0),
                ]
            ],
            
            'account' => [
                'sec-uid' => getString($userData, 'secUid'),
                'short-id' => getString($userData, 'shortId', '未設定'),
                'create-time' => isset($userData['createTime']) && is_numeric($userData['createTime'])
                    ? date('Y-m-d H:i:s', (int)$userData['createTime'])
                    : '不明',
                'nickname-modify-time' => isset($userData['nickNameModifyTime']) && is_numeric($userData['nickNameModifyTime'])
                    ? date('Y-m-d H:i:s', (int)$userData['nickNameModifyTime'])
                    : '不明',
                'region' => getString($userData, 'region', '不明'),
                'language' => getString($userData, 'language', '不明'),
            ],
            
            'settings' => [
                'comment-setting' => translateSetting($userData['commentSetting'] ?? null),
                'duet-setting' => translateSetting($userData['duetSetting'] ?? null),
                'stitch-setting' => translateSetting($userData['stitchSetting'] ?? null),
                'download-setting' => translateSetting($userData['downloadSetting'] ?? null),
            ],
            
            'features' => [
                'ftc' => getBool($userData, 'ftc'),
                'open-favorite' => getBool($userData, 'openFavorite'),
                'tt-seller' => getBool($userData, 'ttSeller'),
                'commerce-user' => $userData['commerceUserInfo']['commerceUser'] ?? null,
            ]
        ];
    }
}
