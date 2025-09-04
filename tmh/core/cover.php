<?php
// TMH by TCSE v0.9.0
// cover.php — безопасный доступ к обложке
// Версия: 1.1 — с централизованной конфигурацией

// === Загрузка конфигурации ===
$config = require_once __DIR__ . '/../data/config.php';

// === Извлечение параметров из конфига ===
$dbFile = $config['db_file'];
$logDir = $config['log_dir'] ?? __DIR__ . '/logs';
$enableLogging = $config['enable_logging'] ?? true;
$defaultCover = $config['player']['default_cover'] ?? 'https://placehold.co/400x400/121212/ffffff?text=🎵';

// === Проверка входных данных ===
$fileId = $_GET['id'] ?? '';
if (empty($fileId)) {
    http_response_code(400);
    exit('No file ID provided');
}

// === Проверка существования базы ===
if (!file_exists($dbFile)) {
    if ($enableLogging) {
        error_log("[cover.php] Database not found: $dbFile");
    }
    http_response_code(500);
    exit('Database not found');
}

// === Загрузка базы данных ===
$db = json_decode(file_get_contents($dbFile), true);
if (!is_array($db) || !isset($db[$fileId])) {
    if ($enableLogging) {
        error_log("[cover.php] Track not found in database: $fileId");
    }
    // Отправляем заглушку
    header('Location: ' . $defaultCover);
    exit;
}

$track = $db[$fileId];
$coverUrl = $track['photo_url'] ?? '';

if (empty($coverUrl)) {
    if ($enableLogging) {
        error_log("[cover.php] No cover URL for track: $fileId");
    }
    header('Location: ' . $defaultCover);
    exit;
}

// === Логирование запроса (если включено) ===
if ($enableLogging) {
    $logFile = "$logDir/cover.log";
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] Cover: $fileId | IP: $remoteAddr | UA: $userAgent | URL: $coverUrl\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// === Установка заголовков и перенаправление ===
header('Content-Type: image/jpeg');
header('Cache-Control: no-cache');
header('Location: ' . $coverUrl);
exit;