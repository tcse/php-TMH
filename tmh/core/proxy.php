<?php
// TMH by TCSE v0.9.0
// player/proxy.php — безопасный доступ к music_db.json
// Версия: 1.2 — с централизованными URL из config.php

// === Загрузка конфигурации ===
$config = require_once __DIR__ . '/../data/config.php';

// === Извлечение параметров из конфига ===
$dbFile = $config['db_file'];
$baseUrl = $config['base_url'];
$enableCors = $config['player']['enable_cors'] ?? true;
$cacheTtl = $config['player']['cache_ttl'] ?? 360;

// === Установка заголовков ===
header('Content-Type: application/json; charset=utf-8');

// CORS — только если включено в конфиге
if ($enableCors) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Кэширование
$expires = gmdate('D, d M Y H:i:s', time() + $cacheTtl) . ' GMT';
header("Cache-Control: public, max-age=$cacheTtl");
header("Expires: $expires");

// === Проверка базы ===
if (!file_exists($dbFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'Database not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = json_decode(file_get_contents($dbFile), true);
if (!is_array($db)) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid database format'], JSON_UNESCAPED_UNICODE);
    exit;
}

// === Форматируем данные для плеера ===
$tracks = [];
foreach ($db as $fileId => $track) {
    $tracks[] = [
        'id' => $fileId,
        'title' => $track['title'] ?? 'Без названия',
        'performer' => $track['performer'] ?? 'Неизвестный',
        'genre' => $track['genre'] ?? '',
        'playlist' => $track['music_playlist'] ?? 'Общая',
        'url' => $baseUrl . '/core/stream.php?id=' . urlencode($fileId),
        'cover' => !empty($track['photo_url']) 
                   ? $baseUrl . '/core/cover.php?id=' . urlencode($fileId) 
                   : null,
        'date' => $track['date_uploaded'] ?? '',
        'user_uploader' => $track['user_uploader'] ?? '',
        'username' => $track['username'] ?? ''
    ];
}

// === Жанровые обложки (можно вынести в config) ===
$genreCovers = [
    ['name' => 'Electronic', 'cover' => 'https://placehold.co/100x100/1DB954/ffffff?text=EDM'],
    ['name' => 'Rock', 'cover' => 'https://placehold.co/100x100/9b59b6/ffffff?text=ROCK'],
    ['name' => 'Hip-Hop', 'cover' => 'https://placehold.co/100x100/f39c12/ffffff?text=HIPHOP'],
    ['name' => 'Pop', 'cover' => 'https://placehold.co/100x100/2ecc71/ffffff?text=POP'],
    ['name' => 'Jazz', 'cover' => 'https://placehold.co/100x100/1abc9c/ffffff?text=JAZZ'],
    ['name' => 'Trap', 'cover' => 'https://placehold.co/100x100/e74c3c/ffffff?text=TRAP'],
    ['name' => 'Lounge', 'cover' => 'https://placehold.co/100x100/3498db/ffffff?text=LOUNGE'],
    ['name' => 'Other', 'cover' => 'https://placehold.co/100x100/95a5a6/ffffff?text=MISC']
];

// === Ответ ===
echo json_encode([
    'tracks' => $tracks,
    'covers' => ['genre' => $genreCovers]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);