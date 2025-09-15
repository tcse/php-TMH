<?php
// /tmh/core/channel_proxy.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

$configFile = __DIR__ . '/../data/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Config file not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

$config = require_once $configFile;
$channel = $config['channel'] ?? [];
$postsFile = $channel['posts_file'] ?? __DIR__ . '/../data/posts.json';

if (!file_exists($postsFile)) {
    echo json_encode(['posts' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$posts = json_decode(file_get_contents($postsFile), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
    exit;
}

$baseUrl = rtrim($config['base_url'], '/');
$flatPosts = [];

foreach ($posts as $post) {
    $item = [
        'id' => $post['id'] ?? null,
        'date' => $post['date'] ?? null,
        'text' => $post['text'] ?? '',
        'caption' => $post['caption'] ?? '',
        'photo_file_id' => $post['photo_file_id'] ?? '',
        'photos' => $post['photos'] ?? [],
        'audio' => $post['audio'] ?? null,
        'video' => $post['video'] ?? null,
        'document' => $post['document'] ?? null,
        'entities' => $post['entities'] ?? [],           // ✅ ДОБАВЛЕНО
        'caption_entities' => $post['caption_entities'] ?? [] // ✅ ДОБАВЛЕНО
    ];

    if (!empty($item['photo_file_id'])) {
        $item['photo_proxy'] = $baseUrl . '/core/blog_cover.php?file_id=' . urlencode($item['photo_file_id']);
    }

    $flatPosts[] = $item;
}

echo json_encode(['posts' => $flatPosts], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);