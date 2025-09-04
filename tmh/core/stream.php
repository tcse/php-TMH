<?php
// TMH by TCSE v0.9.0
// stream.php — безопасный поток аудио через прокси
// Версия: 1.1 — с централизованной конфигурацией

// === Загрузка конфигурации ===
$config = require_once __DIR__ . '/../data/config.php';

// === Извлечение параметров из конфига ===
$dbFile = $config['db_file'];
$logDir = $config['log_dir'] ?? __DIR__ . '/logs';
$enableLogging = $config['enable_logging'] ?? true;
$debug = $config['debug'] ?? false;

// === Проверка входных данных ===
$fileId = $_GET['id'] ?? '';
if (empty($fileId)) {
    http_response_code(400);
    exit('No file ID provided');
}

// === Проверка существования базы ===
if (!file_exists($dbFile)) {
    if ($enableLogging) {
        error_log("[stream.php] Database not found: $dbFile");
    }
    http_response_code(500);
    exit('Database not found');
}

// === Загрузка базы данных ===
$db = json_decode(file_get_contents($dbFile), true);
if (!is_array($db) || !isset($db[$fileId])) {
    if ($enableLogging) {
        $msg = $db === null ? "Invalid JSON in database" : "Track not found: $fileId";
        error_log("[stream.php] $msg");
    }
    http_response_code(404);
    exit('Track not found');
}

// === Получение URL трека ===
$track = $db[$fileId];
$url = $track['url'] ?? '';
if (empty($url)) {
    if ($enableLogging) {
        error_log("[stream.php] Empty URL in database for file_id: $fileId");
    }
    http_response_code(500);
    exit('No URL in database');
}

// === Логирование запроса (если включено) ===
if ($enableLogging) {
    $logFile = "$logDir/stream.log";
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] Stream: $fileId | IP: $remoteAddr | UA: $userAgent | URL: $url\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// === Установка заголовков и перенаправление ===
header('Content-Type: audio/mpeg');
header('Cache-Control: no-cache');
header('Location: ' . $url);
exit;