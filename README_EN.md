# TikTok Profile API

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

English | [日本語](README.md)

A simple PHP API to fetch TikTok user profile information.

## Overview

This API allows you to retrieve TikTok user profile information (follower count, video count, account settings, etc.) in JSON format by specifying a username. It includes rate limiting and caching features.

## Features

- Simple REST API format
- Rate limiting (default: 30 requests per 60 seconds)
- Fast response with caching (default: 5 minutes)
- Secure HTTP headers
- Clean code structure

## Project Structure

```
tiktok-profile-api/
├── config/
│   ├── settings.example.php  # Sample configuration file
│   └── settings.php           # Actual configuration file (needs to be created)
├── src/
│   ├── Classes/
│   │   ├── Cache.php          # Cache management class
│   │   ├── RateLimiter.php    # Rate limiting class
│   │   ├── Response.php       # Response generation class
│   │   └── TikTokClient.php   # TikTok data fetching client
│   ├── Functions/
│   │   └── utils.php          # Utility functions
│   └── autoload.php           # Autoloader
├── index.php                  # API entry point
├── composer.json              # Composer configuration
├── LICENSE                    # License file
├── README.md                  # Japanese documentation
└── README_EN.md               # English documentation
```

## Requirements

- PHP 8.0 or higher
- cURL extension
- JSON extension
- mbstring extension

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/KY0U5UKE/tiktok-profile-api.git
cd tiktok-profile-api
```

### 2. Create configuration file

```bash
cp config/settings.example.php config/settings.php
```

Edit the configuration file as needed.

### 3. Deploy to server

Place the files in your web server's document root and make sure `index.php` is accessible.

## Usage

### Basic Request

```
GET https://your-domain.com/?username=tiktok
```

You can pass the username with or without the `@` symbol. You can also pass the TikTok profile URL directly.

### Response Examples

#### Success

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

#### Error

```json
{
  "success": false,
  "error": "User does not exist",
  "timestamp": 1234567890
}
```

## Configuration

You can customize the following settings in `config/settings.php`:

### Cache Settings

```php
'cache' => [
    'enabled' => true,
    'ttl' => 300,  // Cache for 5 minutes
]
```

### Rate Limit Settings

```php
'rate_limit' => [
    'enabled' => true,
    'max_requests' => 30,  // Maximum requests
    'window' => 60,        // Time window (seconds)
]
```

## Error Codes

- `400` - Parameter error
- `404` - User does not exist
- `429` - Rate limit exceeded
- `500` - Server error

## Notes

- This project is not an official TikTok API
- Only public TikTok profile information can be retrieved
- The API may stop working if TikTok changes its HTML structure
- Please comply with TikTok's Terms of Service when using this API

## License

MIT License - See [LICENSE](LICENSE) file for details.

## Report Issues

If you encounter any issues or have suggestions for improvements, please report them on [GitHub Issues](https://github.com/KY0U5UKE/tiktok-profile-api/issues).