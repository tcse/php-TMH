<?php
// TMH by TCSE v0.9.0
// manifest.php — динамический PWA-манифест
header('Content-Type: application/manifest+json; charset=utf-8');

// Загружаем конфигурацию
$config = require_once __DIR__ . '/../data/config.php';

// Извлекаем нужные параметры
$baseUrl = rtrim($config['base_url'], '/');
$webappUrl = $config['webapp_url'] ?? $baseUrl . '/player.html';
$playerUrl = $config['player_url'] ?? $baseUrl . '/player.html';
$themeColor = '#1DB954'; // можно вынести в config

// Определяем путь к start_url
$parsed = parse_url($playerUrl);
$startPath = $parsed['path'] ?? '/player/';
$dir = dirname($startPath);
$startUrl = rtrim($dir, '/') . '/';

// Формируем манифест
$manifest = [
    'name' => $config['playlist']['title'] ?? 'Telegram Music Hub',
    'short_name' => 'Music Hub',
    'description' => 'Музыкальный плеер для сообщества',
    'start_url' => $startUrl,
    'display' => 'standalone',
    'background_color' => '#121212',
    'theme_color' => $themeColor,
    'orientation' => 'portrait',
    'icons' => [
        [
            'src' => 'logo_192.jpg',
            'sizes' => '192x192',
            'type' => 'image/jpeg',
            'purpose' => 'any maskable'
        ],
        [
            'src' => 'logo_512.jpg',
            'sizes' => '512x512',
            'type' => 'image/jpeg',
            'purpose' => 'any maskable'
        ]
    ],
    'prefer_related_applications' => false
];

echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);