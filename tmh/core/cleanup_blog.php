<?php
// cleanup_blog.php — обновление актуальных ссылок на фото в posts.json
// Запуск: через cron раз в месяц
// URL: https://sitename.com/tmh/cleanup_blog.php?key=mysecret123

$config = require_once __DIR__ . '/../data/config.php';
$token = $config['bot_token'];
$postsFile = $config['channel']['posts_file'] ?? __DIR__ . '/data/posts.json';

// === Защита ===
$accessKey = $_GET['key'] ?? '';
$secretKey = $config['secret_key'] ?? 'mysecret123';
if ($accessKey !== $secretKey) {
    http_response_code(403);
    echo "❌ Доступ запрещён.";
    exit;
}

// === Логирование ===
$output = [];
function logMessage($text, $type = 'info') {
    global $output;
    $time = date('H:i:s');
    $output[] = ['text' => "[$time] $text", 'type' => $type];
    error_log("[cleanup_blog.php] $text");
}

logMessage("Запуск очистки блога", 'info');
if (!file_exists($postsFile)) {
    logMessage("Файл posts.json не найден", 'error');
    exit;
}

$posts = json_decode(file_get_contents($postsFile), true);
$updated = 0;

foreach ($posts as &$post) {
    if (empty($post['photo']) || !isset($post['photo_file_id'])) continue;

    $fileInfo = @json_decode(file_get_contents("https://api.telegram.org/bot$token/getFile?file_id=" . $post['photo_file_id']), true);
    if (!$fileInfo['ok']) {
        logMessage("❌ Фото недоступно: post={$post['id']}", 'error');
        continue;
    }

    $newUrl = "https://api.telegram.org/file/bot$token/" . $fileInfo['result']['file_path'];
    if ($post['photo'] !== $newUrl) {
        $post['photo'] = $newUrl;
        logMessage("🔗 Обновлена ссылка фото: post={$post['id']}", 'success');
        $updated++;
    }
}

if ($updated > 0) {
    file_put_contents($postsFile, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    logMessage("✅ Обновлено $updated фото", 'success');
} else {
    logMessage("✅ Нет изменений", 'info');
}

// === Вывод в браузере ===
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html>
<html lang="ru">
<head><title>Очистка блога</title><style>
body { font-family: Arial; padding: 20px; }
.log { padding: 8px; margin: 5px 0; border-radius: 4px; }
.success { background: #d4edda; color: #155724; }
.error { background: #f8d7da; color: #721c24; }
.info { background: #d1ecf1; color: #0c5460; }
</style></head>
<body>
<h2>🧹 Очистка блога</h2>';
foreach ($output as $entry) {
    $class = $entry['type'];
    echo "<div class='log $class'>" . htmlspecialchars($entry['text']) . "</div>";
}
echo '</body></html>';