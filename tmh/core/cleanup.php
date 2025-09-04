<?php
// TMH by TCSE v0.9.0
// cleanup.php — Проверка актуальности файлов
// Режим: веб (с отображением) или CLI (через cron)

// === Загрузка конфигурации ===
$config = require_once __DIR__ . '/../data/config.php';

// === Извлечение параметров из конфига ===
$token = $config['bot_token'];
$dbFile = $config['db_file'];
$secretKey = $config['secret_key'];
$logDir = $config['log_dir'] ?? __DIR__ . '/logs';
$enableLogging = $config['enable_logging'] ?? true;
$debug = $config['debug'] ?? false;

// === Защита: ключ доступа ===
$accessKey = $_GET['key'] ?? '';
$isCli = php_sapi_name() === 'cli';

if (!$isCli && $accessKey !== $secretKey) {
    http_response_code(403);
    echo "Доступ запрещён.";
    exit;
}

// === Создаём папку для логов, если её нет ===
if ($enableLogging && !is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// === Определяем режим: CLI или веб ===
$output = [];
if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Очистка музыкальной базы</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f4f4f4; color: #333; }
            .log { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin: 10px 0; font-size: 14px; line-height: 1.5; }
            .success { color: #2ecc71; }
            .error { color: #e74c3c; }
            .info { color: #3498db; }
            .warning { color: #f39c12; }
            h1 { color: #2c3e50; }
            .footer { margin-top: 30px; color: #7f8c8d; font-size: 12px; }
        </style>
    </head>
    <body>
        <h1>🧹 Очистка музыкальной базы</h1>
        <div id="output">';
}

// === Функция для логирования ===
function logMessage($text, $type = 'info', $isCli = false) {
    global $output, $enableLogging, $logDir, $debug;

    $time = date('H:i:s');
    $message = "[$time] $text";
    $output[] = ['text' => $message, 'type' => $type];

    if ($isCli && $debug) {
        $prefix = ['info' => 'ℹ️', 'success' => '✅', 'error' => '❌', 'warning' => '⚠️'][$type];
        echo "$prefix $message\n";
    }

    // Логируем в файл, если включено
    if ($enableLogging && in_array($type, ['error', 'warning']) && !empty($logDir)) {
        $logFile = "$logDir/cleanup.log";
        file_put_contents($logFile, "$message\n", FILE_APPEND | LOCK_EX);
    }
}

// === Проверка файла базы ===
if (!file_exists($dbFile)) {
    logMessage("Файл базы не найден: $dbFile", 'error', $isCli);
    if (!$isCli) {
        echo '<div class="log error">❌ Файл базы не найден: ' . htmlspecialchars($dbFile) . '</div>';
        echo '</div></body></html>';
    }
    exit;
}

$db = json_decode(file_get_contents($dbFile), true);
if (!is_array($db)) {
    logMessage("Некорректный формат базы: $dbFile", 'error', $isCli);
    if (!$isCli) {
        echo '<div class="log error">❌ Некорректный формат базы</div>';
        echo '</div></body></html>';
    }
    exit;
}

logMessage("Загружено треков: " . count($db), 'info', $isCli);

$modified = false;
$removedCount = 0;
$updatedCount = 0;

foreach ($db as $fileId => $track) {
    $needsUpdate = false;

    // === Проверка audio file_id ===
    $fileInfo = @json_decode(file_get_contents("https://api.telegram.org/bot$token/getFile?file_id=$fileId"), true);
    if (!$fileInfo['ok']) {
        logMessage("🗑️ Удалён недоступный трек: {$track['title']} (file_id)", 'error', $isCli);
        unset($db[$fileId]);
        $modified = true;
        $removedCount++;
        continue;
    } else {
        $newUrl = "https://api.telegram.org/file/bot$token/" . $fileInfo['result']['file_path'];
        if ($track['url'] !== $newUrl) {
            $db[$fileId]['url'] = $newUrl;
            logMessage("🔗 Обновлена ссылка аудио: {$track['title']}", 'warning', $isCli);
            $needsUpdate = true;
            $updatedCount++;
        }
    }

    // === Проверка photo_file_id (если есть) ===
    if (!empty($track['photo_file_id'])) {
        $photoInfo = @json_decode(file_get_contents("https://api.telegram.org/bot$token/getFile?file_id=" . $track['photo_file_id']), true);
        if (!$photoInfo['ok']) {
            logMessage("🖼️ Обложка недоступна: {$track['title']} — удалена", 'warning', $isCli);
            $db[$fileId]['photo_file_id'] = '';
            $db[$fileId]['photo_url'] = '';
            $needsUpdate = true;
            $updatedCount++;
        } else {
            $newPhotoUrl = "https://api.telegram.org/file/bot$token/" . $photoInfo['result']['file_path'];
            if ($track['photo_url'] !== $newPhotoUrl) {
                $db[$fileId]['photo_url'] = $newPhotoUrl;
                logMessage("🔗 Обновлена ссылка обложки: {$track['title']}", 'warning', $isCli);
                $needsUpdate = true;
                $updatedCount++;
            }
        }
    }

    if ($needsUpdate) {
        $modified = true;
    }
}

// === Сохраняем изменения ===
if ($modified) {
    file_put_contents($dbFile, json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    logMessage("✅ База обновлена: осталось " . count($db) . " треков.", 'success', $isCli);
} else {
    logMessage("✅ База не изменилась — всё в порядке.", 'success', $isCli);
}

// === Вывод в вебе ===
if (!$isCli) {
    foreach ($output as $entry) {
        $class = $entry['type'];
        $text = htmlspecialchars($entry['text']);
        echo "<div class='log $class'>$text</div>";
    }
    echo '</div>
    <div class="footer">
        <p><strong>Статистика:</strong> Проверено: ' . count($output) . ', Удалено: ' . $removedCount . ', Обновлено: ' . $updatedCount . '</p>
        <p><em>Запуск: ' . date('Y-m-d H:i:s') . '</em></p>
    </div>
    </body>
    </html>';
}

// Логирование в CLI
if ($isCli && $debug) {
    foreach ($output as $entry) {
        error_log("[cleanup.php] " . $entry['text']);
    }
}