# TikTok Profile API

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

[English](README_EN.md) | 日本語

TikTokのユーザープロフィール情報を取得するシンプルなPHP APIです。

## 概要

このAPIは、TikTokのユーザーネームを指定することで、そのユーザーのプロフィール情報（フォロワー数、投稿数、アカウント設定など）をJSON形式で取得できます。レート制限とキャッシュ機能を備えています。

## 特徴

- シンプルなREST API形式
- レート制限機能（デフォルト: 60秒間に30リクエスト）
- キャッシュ機能による高速レスポンス（デフォルト: 5分間）
- セキュアなHTTPヘッダー設定
- クリーンなコード構造

## プロジェクト構成

```
tiktok-profile-api/
├── config/
│   ├── settings.example.php  # 設定ファイルのサンプル
│   └── settings.php           # 実際の設定ファイル（要作成）
├── src/
│   ├── Classes/
│   │   ├── Cache.php          # キャッシュ管理クラス
│   │   ├── RateLimiter.php    # レート制限クラス
│   │   ├── Response.php       # レスポンス生成クラス
│   │   └── TikTokClient.php   # TikTokデータ取得クライアント
│   ├── Functions/
│   │   └── utils.php          # ユーティリティ関数
│   └── autoload.php           # オートローダー
├── index.php                  # APIのエントリーポイント
├── composer.json              # Composer設定ファイル
├── LICENSE                    # ライセンスファイル
├── README.md                  # 日本語ドキュメント
└── README_EN.md               # 英語ドキュメント
```

## 必要要件

- PHP 8.0以上
- cURL拡張機能
- JSON拡張機能
- mbstring拡張機能

## インストール

### 1. リポジトリをクローン

```bash
git clone https://github.com/KY0U5UKE/tiktok-profile-api.git
cd tiktok-profile-api
```

### 2. 設定ファイルを作成

```bash
cp config/settings.example.php config/settings.php
```

設定ファイルは必要に応じて編集してください。

### 3. サーバーに配置

Webサーバーのドキュメントルートに配置し、`index.php` にアクセスできるようにしてください。

## 使い方

### 基本的なリクエスト

```
GET https://your-domain.com/?username=tiktok
```

ユーザーネームには `@` を付けても付けなくても動作します。また、TikTokのプロフィールURLをそのまま渡すこともできます。

### レスポンス例

#### 成功時

```json
{
  "success": true,
  "data": {
    "profile": {
      "uid": "1234567890",
      "uniqueid": "tiktok",
      "nickname": "TikTok",
      "signature": "Make Your Day",
      "avatar": "https://...",
      "verified": true,
      "private-account": false
    },
    "stats": {
      "follower-count": 1000000,
      "following-count": 100,
      "video-count": 500,
      "heart-count": 50000000,
      "formatted": {
        "follower-count": "1.0M",
        "video-count": "500",
        "heart-count": "50.0M"
      }
    },
    "account": {
      "region": "JP",
      "language": "ja"
    }
  },
  "timestamp": 1234567890
}
```

#### エラー時

```json
{
  "success": false,
  "error": "ユーザーが存在しません",
  "timestamp": 1234567890
}
```

## 設定のカスタマイズ

`config/settings.php` で以下の設定を変更できます：

### キャッシュ設定

```php
'cache' => [
    'enabled' => true,
    'ttl' => 300,  // 5分間キャッシュ
]
```

### レート制限設定

```php
'rate_limit' => [
    'enabled' => true,
    'max_requests' => 30,  // 最大リクエスト数
    'window' => 60,        // 時間ウィンドウ（秒）
]
```

## 主なエラーコード

- `400` - パラメータエラー
- `404` - ユーザーが存在しない
- `429` - レート制限超過
- `500` - サーバーエラー

## 注意事項

- このプロジェクトはTikTok公式のAPIではありません
- TikTokの公開プロフィール情報のみ取得可能です
- TikTokのHTML構造変更により動作しなくなる可能性があります
- 利用する際はTikTokの利用規約を遵守してください

## ライセンス

MIT License - 詳細は [LICENSE](LICENSE) ファイルを参照してください。

## 問題報告

問題が発生した場合や改善提案がある場合は、[GitHub Issues](https://github.com/KY0U5UKE/tiktok-profile-api/issues) で報告してください。