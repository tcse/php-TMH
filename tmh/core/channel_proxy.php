<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

// === Правильный путь к config.php ===
$configFile = __DIR__ . '/../data/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Config file not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

$config = require_once $configFile;

// === Путь к posts.json из config.php ===
$postsFile = $config['channel']['posts_file'] ?? __DIR__ . '/../data/posts.json';

if (!file_exists($postsFile)) {
    echo json_encode(['posts' => []], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// === Читаем и декодируем JSON ===
$postsContent = file_get_contents($postsFile);
$posts = json_decode($postsContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid JSON in posts.json: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
    exit;
}

// === Генерируем photo_proxy и photo_proxies ===
$baseUrl = $config['base_url'] ?? 'https://tmh.tcse-cms.com/tmh';

$flatPosts = array_values($posts);
foreach ($flatPosts as &$post) {
    // Одиночное фото
    if (!empty($post['photo_file_id'])) {
        $post['photo_proxy'] = $baseUrl . '/core/blog_cover.php?file_id=' . urlencode($post['photo_file_id']);
    }

    // Галерея
    if (!empty($post['photos'])) {
        $post['photo_proxies'] = [];
        foreach ($post['photos'] as $photo) {
            $post['photo_proxies'][] = $baseUrl . '/core/blog_cover.php?file_id=' . urlencode($photo['file_id']);
        }
    }
}

// === Возвращаем в формате { "posts": [...] } ===
echo json_encode([
    'posts' => $flatPosts
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);