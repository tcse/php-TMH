<?php
// TMH by TCSE v0.9.0
// set_webhook.php — установка вебхука Telegram на bot.php
// Версия: 1.1 — с централизованной конфигурацией

// === Загрузка конфигурации ===
$config = require_once __DIR__ . '/../data/config.php';

// === Извлечение параметров из конфига ===
$token = $config['bot_token'];
$webhookUrl = $config['webhook_url'] ?? $config['base_url'] . '/bot.php';
$enableLogging = $config['enable_logging'] ?? false;
$debug = $config['debug'] ?? false;

// === Удаляем старый вебхук ===
$deleteResponse = file_get_contents("https://api.telegram.org/bot$token/deleteWebhook");
if ($enableLogging && $debug) {
    error_log("[set_webhook.php] Удаление вебхука: " . $deleteResponse);
}

// === Устанавливаем новый вебхук ===
$encodedUrl = urlencode($webhookUrl);
$setResponse = file_get_contents("https://api.telegram.org/bot$token/setWebhook?url=$encodedUrl");

// === Проверяем текущий статус вебхука ===
$infoResponse = file_get_contents("https://api.telegram.org/bot$token/getWebhookInfo");
$info = json_decode($infoResponse, true);

// === Вывод результата ===
echo "<h2>📡 Результат установки вебхука</h2>";

echo "<h3>🔹 Удаление предыдущего вебхука:</h3>";
echo "<pre>" . htmlspecialchars($deleteResponse) . "</pre>";

echo "<h3>🔹 Установка нового вебхука ($webhookUrl):</h3>";
echo "<pre>" . htmlspecialchars($setResponse) . "</pre>";

echo "<h3>🔹 Информация о вебхуке:</h3>";
echo "<pre>" . htmlspecialchars(print_r($info, true)) . "</pre>";

// Дополнительно: если включён debug — логируем
if ($debug && $enableLogging) {
    error_log("[set_webhook.php] Webhook установлен: $webhookUrl");
    error_log("[set_webhook.php] Ответ: " . $setResponse);
}