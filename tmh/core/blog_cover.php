<?php
header('Content-Type: image/jpeg');
header('Cache-Control: no-cache');

$config = require_once __DIR__ . '/../data/config.php';
$token = $config['bot_token'];

$fileId = $_GET['file_id'] ?? '';
if (empty($fileId)) {
    http_response_code(400);
    exit('No file_id provided');
}

$fileInfo = @json_decode(file_get_contents("https://api.telegram.org/bot$token/getFile?file_id=$fileId"), true);
if (!$fileInfo['ok']) {
    header('Location: https://placehold.co/400x400/121212/ffffff?text=📷');
    exit;
}

$filePath = $fileInfo['result']['file_path'];
$coverUrl = "https://api.telegram.org/file/bot$token/$filePath";

// Лог (опционально)
file_put_contents(__DIR__ . '/../data/blog_cover.log', date('Y-m-d H:i:s') . " - Cover: file_id=$fileId\n", FILE_APPEND);

header('Location: ' . $coverUrl);
exit;