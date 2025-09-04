<?php
// TMH by TCSE v0.9.0
// generate_playlist.php — безопасный генератор плейлистов
// Версия: 1.2 — с централизованной конфигурацией

// === Загрузка конфигурации ===
$config = require_once __DIR__ . '/../data/config.php';

// === Извлечение параметров из конфига ===
$baseUrl = $config['base_url'];
$dbFile = $config['db_file'];
$playlistConfig = $config['playlist'] ?? [];
$playerConfig = $config['player'] ?? [];
$enableLogging = $config['enable_logging'] ?? true;

// === Проверка существования базы ===
if (!file_exists($dbFile)) {
    if (isset($_GET['format'])) {
        http_response_code(404);
        echo "Ошибка: база данных не найдена.";
        exit;
    } else {
        renderPage([
            'title' => '❌ Ошибка',
            'content' => '<p>База треков не найдена. Попробуйте позже.</p>'
        ]);
        exit;
    }
}

$db = json_decode(file_get_contents($dbFile), true);
if (!is_array($db)) {
    if (isset($_GET['format'])) {
        http_response_code(500);
        echo "Ошибка: некорректный формат базы данных.";
        exit;
    } else {
        renderPage([
            'title' => '❌ Ошибка',
            'content' => '<p>Некорректный формат базы данных.</p>'
        ]);
        exit;
    }
}

// === Определяем формат ===
$format = strtolower($_GET['format'] ?? '');
$validFormats = $config['playlist']['formats'] ?? ['m3u', 'pls', 'xspf'];

if (!in_array($format, $validFormats)) {
    // === Режим: показать страницу с выбором ===
    $count = count($db);
    $links = [];
    foreach ($validFormats as $fmt) {
        $links[$fmt] = "?format=" . urlencode($fmt);
    }

    $pageContent = "
        <h2>🎧 Скачайте плейлист</h2>
        <p>Всего треков: <strong>{$count}</strong></p>
        <div class='format-links'>
            <a href='{$links['m3u']}' class='format-btn'>M3U (универсальный)</a>
            <a href='{$links['pls']}' class='format-btn'>PLS (Winamp)</a>
            <a href='{$links['xspf']}' class='format-btn'>XSPF (VLC с обложками)</a>
        </div>
        <p><small>Плейлист содержит ссылки на треки через безопасный прокси. Токен бота скрыт, ссылки всегда актуальны.</small></p>
    ";

    renderPage([
        'title' => '🎵 Плейлисты ' . ($playlistConfig['title'] ?? 'Музыкальный плейлист'),
        'content' => $pageContent
    ]);
    exit;
}

// === Генерация по формату с прокси-ссылками ===
$filename = $playlistConfig['filename'] ?? 'MusicPlaylist';
$title = $playlistConfig['title'] ?? 'Музыкальный плейлист';
$creator = $playlistConfig['creator'] ?? 'Telegram Music Bot';

switch ($format) {
    case 'm3u':
        header('Content-Type: text/plain; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.m3u\"; filename*=UTF-8''{$filename}.m3u");
        echo "#EXTM3U\n";
        foreach ($db as $fileId => $track) {
            $titleTrack = $track['title'] ?? 'Без названия';
            $performer = $track['performer'] ?? 'Неизвестный исполнитель';
            $genre = $track['genre'] ?? '';
            $duration = $track['duration'] ?? 0;

            // 🔗 Прокси-ссылка на аудио
            $proxyUrl = "{$baseUrl}/core/stream.php?id=" . urlencode($fileId);

            $line = "#EXTINF:{$duration},{$performer} - {$titleTrack}";
            if ($genre) $line .= " [{$genre}]";
            echo $line . "\n";
            echo $proxyUrl . "\n";
        }
        break;

    case 'pls':
        header('Content-Type: text/plain; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.pls\"; filename*=UTF-8''{$filename}.pls");
        echo "[playlist]\n";
        $index = 1;
        foreach ($db as $fileId => $track) {
            $titleTrack = $track['title'] ?? 'Без названия';
            $performer = $track['performer'] ?? 'Неизвестный';
            $duration = $track['duration'] ?? 0;

            // 🔗 Прокси-ссылка на аудио
            $proxyUrl = "{$baseUrl}/core/stream.php?id=" . urlencode($fileId);

            echo "File{$index}={$proxyUrl}\n";
            echo "Title{$index}={$performer} - {$titleTrack}\n";
            echo "Length{$index}={$duration}\n";
            $index++;
        }
        echo "NumberOfEntries=" . ($index - 1) . "\n";
        echo "Version=2\n";
        break;

    case 'xspf':
        header('Content-Type: application/xspf+xml; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.xspf\"; filename*=UTF-8''{$filename}.xspf");
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        ?>
<playlist version="1" xmlns="http://xspf.org/ns/0/">
    <title><?= htmlspecialchars($title) ?></title>
    <creator><?= htmlspecialchars($creator) ?></creator>
    <info><?= htmlspecialchars($baseUrl) ?></info>
    <trackList>
        <?php foreach ($db as $fileId => $track): ?>
        <track>
            <!-- 🔗 Прокси-ссылка на аудио -->
            <location><?= htmlspecialchars("{$baseUrl}/core/stream.php?id=" . urlencode($fileId)) ?></location>
            <title><?= htmlspecialchars($track['title'] ?? 'Без названия') ?></title>
            <creator><?= htmlspecialchars($track['performer'] ?? 'Неизвестный') ?></creator>
            <annotation><?= htmlspecialchars($track['genre'] ?? '') ?></annotation>
            <duration><?= ($track['duration'] ?? 0) * 1000 ?></duration>
            <?php if (!empty($track['photo_url'])): ?>
            <!-- 🖼️ Прокси-ссылка на обложку -->
            <image><?= htmlspecialchars("{$baseUrl}/core/cover.php?id=" . urlencode($fileId)) ?></image>
            <?php endif; ?>
        </track>
        <?php endforeach; ?>
    </trackList>
</playlist>
        <?php
        break;
}
exit;

// === Вспомогательная функция: рендер страницы ===
function renderPage($data) {
    $config = require_once __DIR__ . '/../data/config.php';
    $title = htmlspecialchars($data['title'] ?? 'Плейлисты');
    $content = $data['content'] ?? '';
    $baseUrl = $config['base_url'];
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #121212;
            color: #e0e0e0;
            margin: 0;
            padding: 40px 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #1e1e1e;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            text-align: center;
        }
        h1, h2 {
            color: #1DB954;
        }
        p {
            color: #aaa;
        }
        .format-links {
            margin: 30px 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .format-btn {
            display: block;
            padding: 14px;
            background: #2c2c2c;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid #3a3a3a;
        }
        .format-btn:hover {
            background: #3a3a3a;
            transform: translateY(-2px);
        }
        footer {
            margin-top: 40px;
            font-size: 0.9rem;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= $title ?></h1>
        <?= $content ?>
        <footer>© <?= htmlspecialchars(parse_url($baseUrl, PHP_URL_HOST)) ?> | Онлайн-плейлист</footer>
    </div>
</body>
</html>
    <?php
}