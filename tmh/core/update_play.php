<?php
// TMH by TCSE v0.9.1
// update_play.php — увеличение счётчика прослушиваний
// Версия: 1.1 — с централизованной конфигурацией

// === Загрузка конфигурации ===
$config = require_once __DIR__ . '/../data/config.php';

// === Извлечение параметров из конфига ===
$dbFile = $config['db_file'];
$enableLogging = $config['enable_logging'] ?? true;
$logDir = $config['log_dir'] ?? __DIR__ . '/logs';
$enableCors = $config['player']['enable_cors'] ?? true;

// === Установка заголовков ===
header('Content-Type: application/json; charset=utf-8');

// CORS — только если включено в конфиге
if ($enableCors) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
}

// === Получение данных ===
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['file_id'])) {
    echo json_encode(['success' => false, 'error' => 'No file_id provided']);
    exit;
}
$fileId = $input['file_id'];

// === Проверка базы ===
if (!file_exists($dbFile)) {
    if ($enableLogging) {
        error_log("[update_play.php] Database not found: $dbFile");
    }
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Database not found']);
    exit;
}

$db = json_decode(file_get_contents($dbFile), true);
if (!is_array($db) || !isset($db[$fileId])) {
    if ($enableLogging) {
        error_log("[update_play.php] Track not found in database: $fileId");
    }
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Track not found']);
    exit;
}

// === Увеличение счётчика (только если включено) ===
if ($config['enable_play_count']) {
    $debounceSec = $config['play_count_debounce_sec'] ?? 30;

    // Анти-флуд: один раз в N секунд с IP
    $logFile = "$logDir/update_play.log";
    if (file_exists($logFile)) {
        $logs = array_filter(file($logFile, FILE_IGNORE_NEW_LINES));
        $recent = array_filter($logs, function($log) use ($fileId, $remoteAddr, $debounceSec) {
            if (preg_match('/Play incremented: (\S+) \| IP: (\S+) \|/', $log, $m)) {
                $ts = strtotime(substr($log, 1, 19));
                return $m[1] === $fileId && $m[2] === $remoteAddr && (time() - $ts) < $debounceSec;
            }
            return false;
        });
        if (!empty($recent)) {
            // Пропускаем запись, но отвечаем 200
            echo json_encode(['success' => true, 'message' => 'Debounced', 'new_count' => $db[$fileId]['count_play']]);
            exit;
        }
    }

    // Увеличиваем и сохраняем
    $db[$fileId]['count_play'] = ($db[$fileId]['count_play'] ?? 0) + 1;
    $updatedCount = $db[$fileId]['count_play'];

    if (!file_put_contents($dbFile, json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Write failed']);
        exit;
    }
} else {
    // Счётчик выключен
    $updatedCount = $db[$fileId]['count_play'] ?? 0;
}

// === Логирование (если включено) ===
if ($enableLogging) {
    $logFile = "$logDir/update_play.log";
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] Play incremented: $fileId | IP: $remoteAddr | New count: $updatedCount\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// === Ответ ===
echo json_encode([
    'success' => true,
    'file_id' => $fileId,
    'new_count' => $updatedCount
]);
