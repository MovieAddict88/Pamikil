<?php
/**
 * Core Application Configuration
 */

return [
    'app' => [
        'name' => 'Karaoke App',
        'env' => $_ENV['APP_ENV'] ?? 'development',
        'debug' => $_ENV['APP_DEBUG'] ?? true,
        'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
        'timezone' => 'UTC',
        'key' => $_ENV['APP_KEY'] ?? 'your-secret-key-here',
    ],
    
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'database' => $_ENV['DB_DATABASE'] ?? 'karaoke_app',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],
    
    'session' => [
        'driver' => 'file',
        'lifetime' => 120,
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => __DIR__ . '/../storage/sessions',
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => 'karaoke_session',
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'http_only' => true,
    ],
    
    'cache' => [
        'default' => 'file',
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../storage/cache',
            ],
        ],
    ],
    
    'youtube' => [
        'api_key' => $_ENV['YOUTUBE_API_KEY'] ?? '',
        'max_results' => 50,
        'region' => 'US',
    ],
    
    'uploads' => [
        'max_size' => 100 * 1024 * 1024, // 100MB
        'allowed_types' => [
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'],
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'audio' => ['mp3', 'wav', 'flac', 'aac', 'ogg'],
        ],
        'upload_path' => __DIR__ . '/../public/uploads',
    ],
    
    'karaoke' => [
        'max_queue_items' => 100,
        'default_room_size' => 20,
        'max_room_size' => 100,
        'queue_timeout' => 3600, // 1 hour
        'scoring' => [
            'max_score' => 1000,
            'pitch_weight' => 0.6,
            'timing_weight' => 0.4,
        ],
    ],
    
    'websocket' => [
        'host' => $_ENV['WS_HOST'] ?? 'localhost',
        'port' => $_ENV['WS_PORT'] ?? '8080',
        'ssl' => false,
    ],
];