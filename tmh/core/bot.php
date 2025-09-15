<?php
// TMH by TCSE v0.9.3.1
// bot.php — полностью исправленная версия с правами,  модерацией
// ✅ Устранены пробелы в URL
// ✅ Добавлена функция hasPermission()
// ✅ Исправлена работа всех команд

// === Загрузка конфигурации ===
$config = require_once __DIR__ . '/../data/config4.php'; // ✅

// === Извлечение параметров ===
$token = $config['bot_token'];
$dbFile = $config['db_file'];
$stateFile = $config['state_file'];
$enableLogging = $config['enable_logging'] ?? true;
$debug = $config['debug'] ?? false;

// === Загрузка ролей и прав ===
$permissions = $config['permissions'] ?? [];
$roles = $permissions['roles'] ?? [];
$roleMap = $permissions['role_map'] ?? [];

// === Получение обновления ===
$update = json_decode(file_get_contents('php://input'), true);

// === Извлечение сообщения и поста из канала ===
$message = $update['message'] ?? null;
$channelPost = $update['channel_post'] ?? null;
$chatId = null;
$userId = null;

if ($message) {
    $chatId = $message['chat']['id'] ?? null;
    $userId = $message['from']['id'] ?? null;
} elseif ($channelPost) {
    $chatId = $channelPost['chat']['id'] ?? null;
} else {
    exit;
}

if (!$chatId) exit;

// === Определение роли пользователя ===
function getUserRole($userId, $roleMap) {
    if (in_array($userId, $roleMap['admin'] ?? [])) return 'admin';
    if (in_array($userId, $roleMap['trusted'] ?? [])) return 'trusted';
    return 'guest';
}

$role = getUserRole($chatId, $roleMap);

// === Загрузка базы и состояний ===
$db = file_exists($dbFile) ? json_decode(file_get_contents($dbFile), true) : [];
$states = file_exists($stateFile) ? json_decode(file_get_contents($stateFile), true) : [];

// === Проверка прав ===
function hasPermission($role, $perm, $roles) {
    return $roles[$role][$perm] ?? false;
}

// === Может ли пользователь загружать? ===
function canUpload($role, $roles) {
    return $roles[$role]['can_upload'] ?? false;
}

// === Если это пост из канала ===
if ($channelPost) {
    handleChannelPost($channelPost, $config, $token);
    exit;
}

// === Обработка аудио ===
if (isset($message['audio'])) {
    if (!canUpload($role, $roles)) {
        $playerUrl = $config['webapp_url'] ?? 'https://tmh.tcse-cms.com/tmh/player.html';
        $keyboard = json_encode([
            'inline_keyboard' => [[
                ['text' => '🎧 Только прослушивание', 'web_app' => ['url' => $playerUrl]]
            ]]
        ]);
        sendMessage($chatId, "❌ У вас нет прав на загрузку треков.", false, $keyboard);
        exit;
    }

    $audio = $message['audio'];
    $fileId = $audio['file_id'];

    if (isset($db[$fileId])) {
        sendMessage($chatId, "⚠️ Этот трек уже в базе.");
        exit;
    }

    $fileInfo = json_decode(file_get_contents("https://api.telegram.org/bot$token/getFile?file_id=$fileId"), true);
    if (!$fileInfo['ok']) {
        sendMessage($chatId, "❌ Ошибка получения файла.");
        exit;
    }
    $filePath = $fileInfo['result']['file_path'];
    $directUrl = "https://api.telegram.org/file/bot$token/$filePath";

    $originalFilename = $message['caption'] ?? '';
    if (empty($originalFilename) && isset($audio['file_name'])) {
        $originalFilename = $audio['file_name'];
    }
    $originalFilename = preg_replace('/\\.[^.\\s]{1,4}$/', '', $originalFilename);
    $originalFilename = trim($originalFilename);

    $states[$userId] = [
        'step' => 'title',
        'file_id' => $fileId,
        'file_unique_id' => $audio['file_unique_id'],
        'duration' => $audio['duration'],
        'file_size' => $audio['file_size'] ?? 0,
        'date_file' => date('Y-m-d H:i:s', $audio['file_date'] ?? time()),
        'url' => $directUrl,
        'user_first_name' => $message['from']['first_name'],
        'original_filename' => $originalFilename
    ];
    file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    $prompt = "🎵 Введите название трека.";
    if (!empty($originalFilename)) {
        $prompt .= "\n📝 Предлагаемое название: *$originalFilename*";
    }
    $prompt .= "\n💡 Пропустите: /skipstep\n💡 Отмена: /cancel";
    sendMessage($chatId, $prompt, true);
    exit;
}

// === Обработка фото (обложка) ===
if (isset($message['photo']) && isset($states[$userId]) && $states[$userId]['step'] === 'awaiting_photo') {
    $photos = $message['photo'];
    $photo = end($photos);
    $fileId = $photo['file_id'];
    $fileInfo = json_decode(file_get_contents("https://api.telegram.org/bot$token/getFile?file_id=$fileId"), true);
    if (!$fileInfo['ok']) {
        sendMessage($chatId, "❌ Не удалось сохранить обложку.");
        exit;
    }
    $filePath = $fileInfo['result']['file_path'];
    $photoUrl = "https://api.telegram.org/file/bot$token/$filePath";
    $states[$userId]['photo_file_id'] = $fileId;
    $states[$userId]['photo_url'] = $photoUrl;
    $states[$userId]['step'] = 'done';
    file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    sendMessage($chatId, "🖼️ Обложка сохранена. Сохраняем трек...");

    $data = $states[$userId];
    unset($states[$userId]);

    $username = $message['from']['username'] ?? '';

    $status = 'approved';
    $moderation = $config['moderation'] ?? [];
    if (($moderation['enable'] ?? false) && !in_array($chatId, $moderation['auto_approve_for'] ?? [])) {
        $status = 'pending';
    }

    $db[$data['file_id']] = [
        'title' => $data['title'],
        'performer' => $data['performer'],
        'genre' => $data['genre'],
        'music_playlist' => $data['music_playlist'],
        'album' => '',
        'user_uploader' => (string)$userId,
        'username' => $username,
        'first_name' => $message['from']['first_name'],
        'date_uploaded' => date('Y-m-d H:i:s'),
        'file_id' => $data['file_id'],
        'file_unique_id' => $data['file_unique_id'],
        'url' => $data['url'],
        'duration' => $data['duration'],
        'file_size' => $data['file_size'],
        'count_play' => 0,
        'count_downloads' => 0,
        'tags' => [],
        'status' => $status,
        'photo_file_id' => $data['photo_file_id'],
        'photo_url' => $data['photo_url']
    ];

    file_put_contents($dbFile, json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    if ($status === 'pending') {
        sendMessage($chatId, "✅ Трек «{$data['title']}» отправлен на модерацию.\nОжидайте одобрения.");
        if ($moderation['notify_admin_on_upload'] ?? false) {
            $msg = "🆕 Новый трек на модерации:\n";
            $msg .= "🎵 «{$data['title']}» — {$data['performer']}\n";
            $msg .= "👤 От: @{$username} (ID: $userId)\n";
            $msg .= "📥 /approve_{$data['file_id']} — одобрить\n";
            $msg .= "❌ /reject_{$data['file_id']} — отклонить";

            foreach ($moderation['admin_chat_ids'] ?? [] as $adminId) {
                sendMessage($adminId, $msg);
            }
        }
    } else {
        sendMessage($chatId, "✅ Трек «{$data['title']}» добавлен в базу!\n🎵 Ваши треки: /my_tracks\n📌 Справка: /help");
    }

    exit;
}

// === Обработка текста ===
if (isset($message['text'])) {
    $text = trim($message['text']);

    if ($text === '/add_channel_music' && $role === 'admin') {
        addChannelMusic($chatId, $config, $db, $dbFile);
        exit;
    }

    $firstName = $states[$userId]['user_first_name'] ?? 'Пользователь';

    // === /my_id — показать свой chat_id ===
    if ($text === '/my_id') {
        sendMessage($chatId, "Ваш chat_id: `$chatId`", true);
        exit;
    }

    // === /my_role — показать роль ===
    if ($text === '/my_role') {
        $msg = "Ваша роль: *$role*\n";
        $perms = $roles[$role];
        foreach ($perms as $p => $value) {
            $msg .= "- $p: " . ($value ? '✅' : '❌') . "\n";
        }
        sendMessage($chatId, $msg, true);
        exit;
    }

    // === Команды модерации: /approve_... /reject_... — только для admin ===
    if (strpos($text, '/approve_') === 0 && $role === 'admin') {
        $fileId = substr($text, 9);
        approveTrack($fileId, $chatId, $config, $db, $dbFile);
        exit;
    }
    if (strpos($text, '/reject_') === 0 && $role === 'admin') {
        $fileId = substr($text, 8);
        rejectTrack($fileId, $chatId, $config, $db, $dbFile);
        exit;
    }

    // === /playlist и подкоманды — доступны ВСЕМ ===
    if ($text === '/playlist') {
        $msg = "🎧 Скачайте плейлист:\n";
        $msg .= "• M3U: /get_m3u\n";
        $msg .= "• PLS: /get_pls\n";
        $msg .= "• XSPF: /get_xspf";
        sendMessage($chatId, $msg);
        exit;
    }

    if ($text === '/get_m3u') {
        $url = $config['base_url'] . '/playlist.m3u';
        sendMessage($chatId, "📥 M3U-плейлист:\n$url");
        exit;
    }
    if ($text === '/get_pls') {
        $url = $config['base_url'] . '/playlist.pls';
        sendMessage($chatId, "📥 PLS-плейлист:\n$url");
        exit;
    }
    if ($text === '/get_xspf') {
        $url = $config['base_url'] . '/playlist.xspf';
        sendMessage($chatId, "📥 XSPF-плейлист:\n$url");
        exit;
    }

    // === /start и /help — разный контент для ролей ===
    if ($text === '/start') {
        $playerUrl = $config['webapp_url'] ?? 'https://tmh.tcse-cms.com/tmh/player.html';
        $keyboard = json_encode([
            'inline_keyboard' => [[
                ['text' => '🎧 Запустить плеер', 'web_app' => ['url' => $playerUrl]]
            ]]
        ]);

        if ($role === 'guest') {
            sendMessage($chatId, "🎵 Добро пожаловать!\n\nВы можете слушать музыку через наш плеер.\nИли скачайте плейлист для любимого проигрывателя: /playlist", false, $keyboard);
        } else {
            $msg = "🎵 Отправьте аудио, чтобы добавить трек.\n";
            $msg .= "💡 Или пропустите шаг: /skipstep\n💡 Отмена: /cancel\n";
            $msg .= "📌 Справка: /help";
            sendMessage($chatId, $msg, false, $keyboard);
        }
        exit;
    }

    if ($text === '/help') {
        if ($role === 'guest') {
            $msg = "📌 Доступные команды:\n";
            $msg .= "/playlist — скачать плейлист\n";
            $msg .= "\n🎧 Используйте плеер для прослушивания.";
            sendMessage($chatId, $msg, false, json_encode([
                'inline_keyboard' => [[
                    ['text' => '🎧 Запустить плеер', 'web_app' => ['url' => $config['webapp_url']]]
                ]]
            ]));
            exit;
        }

        $msg = "📌 Как добавить трек:\n";
        $msg .= "1. Отправьте аудио\n";
        $msg .= "2. Введите название, исполнителя, жанр\n";
        $msg .= "3. Отправьте обложку\n";
        $msg .= "📌 Команды:\n";
        $msg .= "/my_tracks — ваши треки\n";
        $msg .= "/top — топ прослушиваний\n";
        $msg .= "/top_users — топ авторов\n";
        $msg .= "/top_genre — топ жанров\n";
        $msg .= "/playlist — скачать плейлист\n";
        $msg .= "/cancel — отменить\n";
        $msg .= "/skipstep — пропустить шаг\n";
        $msg .= "/skipphoto — пропустить обложку";
        if ($role === 'admin') {
            $msg .= "\n🛠️ Админ:\n";
            $msg .= "/approve_... — одобрить\n";
            $msg .= "/reject_... — отклонить";
        }
        sendMessage($chatId, $msg, false, json_encode([
            'inline_keyboard' => [[
                ['text' => '🎧 Запустить плеер', 'web_app' => ['url' => $config['webapp_url']]]
            ]]
        ]));
        exit;
    }

    // === Команды, требующие прав ===
    if ($text === '/my_tracks' && hasPermission($role, 'can_see_stats', $roles)) {
        $userTracks = array_filter($db, function($t) use ($chatId) {
            return $t['user_uploader'] == $chatId && ($t['status'] === 'approved' || $t['status'] === 'pending');
        });
        if (empty($userTracks)) {
            sendMessage($chatId, "У вас пока нет треков.");
        } else {
            sendMessage($chatId, "Ваши треки (" . count($userTracks) . "):");
            foreach ($userTracks as $track) {
                sendAudioWithCover($chatId, $track['file_id'], $track['title'], $track['performer'], $track['photo_file_id'], true);
            }
        }
        exit;
    }

    if ($text === '/top' && hasPermission($role, 'can_see_stats', $roles)) {
        $approved = array_filter($db, fn($t) => $t['status'] === 'approved');
        usort($approved, fn($a, $b) => $b['count_play'] <=> $a['count_play']);
        $top = array_slice($approved, 0, 5);
        sendMessage($chatId, "🎧 Топ-5 по прослушиваниям:");
        foreach ($top as $track) {
            sendAudioWithCover($chatId, $track['file_id'], $track['title'], $track['performer'], $track['photo_file_id'], true);
        }
        exit;
    }

    if ($text === '/top_users' && hasPermission($role, 'can_see_stats', $roles)) {
        $userCount = [];
        foreach ($db as $track) {
            if ($track['status'] !== 'approved') continue;
            $username = $track['username'] ?: 'user_' . $track['user_uploader'];
            $userCount[$username] = ($userCount[$username] ?? 0) + 1;
        }
        arsort($userCount);
        $top = array_slice($userCount, 0, 10);
        $msg = "👥 Топ-10 авторов:\n";
        foreach ($top as $username => $count) {
            $msg .= "/tracks_$username - Всего треков ($count)\n";
        }
        sendMessage($chatId, $msg);
        exit;
    }

    if ($text === '/top_genre' && hasPermission($role, 'can_see_stats', $roles)) {
        $genreCount = [];
        foreach ($db as $track) {
            if ($track['status'] !== 'approved') continue;
            $genre = $track['genre'] ?: 'Unknown';
            $genreCount[$genre] = ($genreCount[$genre] ?? 0) + 1;
        }
        arsort($genreCount);
        $top = array_slice($genreCount, 0, 5);
        $msg = "🎼 Топ-5 жанров:\n";
        foreach ($top as $genre => $count) {
            $cmd = str_replace([' ', '.', ',', '-', '&', '/', '\\', '@', '#'], '_', $genre);
            $msg .= "/genre_$cmd - Всего ($count)\n";
        }
        sendMessage($chatId, $msg);
        exit;
    }

    if (strpos($text, '/tracks_') === 0 && hasPermission($role, 'can_see_stats', $roles)) {
        $requestedUsername = substr($text, 8);
        $userTracks = array_filter($db, function($track) use ($requestedUsername) {
            if ($track['status'] !== 'approved') return false;
            $username = $track['username'] ?: 'user_' . $track['user_uploader'];
            return $username === $requestedUsername;
        });
        if (empty($userTracks)) {
            sendMessage($chatId, "❌ У пользователя @$requestedUsername нет треков.");
        } else {
            sendMessage($chatId, "🎵 Треки пользователя @$requestedUsername (" . count($userTracks) . "):");
            foreach ($userTracks as $track) {
                sendAudioWithCover($chatId, $track['file_id'], $track['title'], $track['performer'], $track['photo_file_id'], true);
            }
        }
        exit;
    }

    if (strpos($text, '/genre_') === 0 && hasPermission($role, 'can_see_stats', $roles)) {
        $requestedGenreCmd = substr($text, 7);
        $requestedGenre = str_replace('_', ' ', $requestedGenreCmd);
        $genreTracks = array_filter($db, function($track) use ($requestedGenre) {
            if ($track['status'] !== 'approved') return false;
            return strtolower($track['genre']) === strtolower($requestedGenre);
        });
        if (empty($genreTracks)) {
            sendMessage($chatId, "❌ В жанре *$requestedGenre* пока нет треков.", true);
        } else {
            sendMessage($chatId, "🎧 Треки в жанре *$requestedGenre* (" . count($genreTracks) . "):", true);
            foreach ($genreTracks as $track) {
                sendAudioWithCover($chatId, $track['file_id'], $track['title'], $track['performer'], $track['photo_file_id'], true);
            }
        }
        exit;
    }

    // === /cancel, /skipphoto, /skipstep — только для trusted+ ===
    if ($text === '/cancel') {
        if (isset($states[$userId])) {
            unset($states[$userId]);
            file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            sendMessage($chatId, "❌ Добавление трека отменено.");
        } else {
            sendMessage($chatId, "Нечего отменять.");
        }
        exit;
    }

    if ($text === '/skipphoto' && hasPermission($role, 'can_upload', $roles)) {
        if (isset($states[$userId]) && $states[$userId]['step'] === 'awaiting_photo') {
            sendMessage($chatId, "🖼️ Пропускаем обложку...");
            $states[$userId]['photo_file_id'] = '';
            $states[$userId]['photo_url'] = '';
            $states[$userId]['step'] = 'done';
            file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $data = $states[$userId];
            unset($states[$userId]);
            $username = $message['from']['username'] ?? '';
            $status = 'approved';
            if (($moderation['enable'] ?? false) && !in_array($chatId, $moderation['auto_approve_for'] ?? [])) {
                $status = 'pending';
            }
            $db[$data['file_id']] = [
                'title' => $data['title'],
                'performer' => $data['performer'],
                'genre' => $data['genre'],
                'music_playlist' => $data['music_playlist'],
                'album' => '',
                'user_uploader' => (string)$userId,
                'username' => $username,
                'first_name' => $message['from']['first_name'],
                'date_uploaded' => date('Y-m-d H:i:s'),
                'file_id' => $data['file_id'],
                'file_unique_id' => $data['file_unique_id'],
                'url' => $data['url'],
                'duration' => $data['duration'],
                'file_size' => $data['file_size'],
                'count_play' => 0,
                'count_downloads' => 0,
                'tags' => [],
                'status' => $status,
                'photo_file_id' => '',
                'photo_url' => ''
            ];
            file_put_contents($dbFile, json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            if ($status === 'pending') {
                sendMessage($chatId, "✅ Трек «{$data['title']}» отправлен на модерацию.");
                if ($moderation['notify_admin_on_upload'] ?? false) {
                    $msg = "🆕 На модерации: «{$data['title']}» от @{$username}\n";
                    $msg .= "📥 /approve_{$data['file_id']} — одобрить\n";
                    $msg .= "❌ /reject_{$data['file_id']} — отклонить";
                    foreach ($moderation['admin_chat_ids'] ?? [] as $adminId) {
                        sendMessage($adminId, $msg);
                    }
                }
            } else {
                sendMessage($chatId, "✅ Трек добавлен в базу!");
            }
            exit;
        } else {
            sendMessage($chatId, "❌ Команда /skipphoto доступна только на шаге обложки.");
            exit;
        }
    }

    if ($text === '/skipstep' && hasPermission($role, 'can_upload', $roles)) {
        if (!isset($states[$userId])) {
            handleCommand($chatId, $text, $db, $token, $config);
            exit;
        }
        $step = $states[$userId]['step'];
        switch ($step) {
            case 'title':
                $useTitle = !empty($states[$userId]['original_filename']) ? $states[$userId]['original_filename'] : "Загружено от $firstName";
                $states[$userId]['title'] = $useTitle;
                $states[$userId]['step'] = 'performer';
                file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                sendMessage($chatId, "👤 Введите исполнителя.\n💡 Или пропустите: /skipstep\n💡 Отмена: /cancel");
                exit;
            case 'performer':
                $states[$userId]['performer'] = "V.A. (от $firstName)";
                $states[$userId]['step'] = 'genre';
                file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                sendMessage($chatId, "🎼 Введите жанр.\n💡 Или пропустите: /skipstep\n💡 Отмена: /cancel");
                exit;
            case 'genre':
                $states[$userId]['genre'] = 'Unknown';
                $states[$userId]['step'] = 'playlist';
                file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                sendMessage($chatId, "📁 Введите название плейлиста.\n💡 Или пропустите: /skipstep\n💡 Отмена: /cancel");
                exit;
            case 'playlist':
                $states[$userId]['music_playlist'] = 'Общая';
                $states[$userId]['step'] = 'awaiting_photo';
                file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                sendMessage($chatId, "🖼️ Отправьте обложку.\n💡 Или пропустите: /skipphoto\n💡 Отменить: /cancel");
                exit;
        }
    }

    // === Ожидается фото, а пришёл текст ===
    if (isset($states[$userId]) && $states[$userId]['step'] === 'awaiting_photo') {
        sendMessage($chatId, "🖼️ Пожалуйста, отправьте изображение (фото), а не текст.\n💡 Или пропустите: /skipphoto\n💡 Отменить: /cancel");
        exit;
    }

    // === Обновление состояния ===
    if (isset($states[$userId]) && hasPermission($role, 'can_upload', $roles)) {
        $step = $states[$userId]['step'];
        switch ($step) {
            case 'title':
                $states[$userId]['title'] = strlen($text) < 3 ? ($states[$userId]['original_filename'] ?: "Загружено от $firstName") : $text;
                $states[$userId]['step'] = 'performer';
                file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                sendMessage($chatId, "👤 Введите исполнителя.\n💡 Или пропустите: /skipstep\n💡 Отмена: /cancel");
                exit;
            case 'performer':
                $states[$userId]['performer'] = strlen($text) < 3 ? "V.A. (от $firstName)" : $text;
                $states[$userId]['step'] = 'genre';
                file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                sendMessage($chatId, "🎼 Введите жанр.\n💡 Или пропустите: /skipstep\n💡 Отмена: /cancel");
                exit;
            case 'genre':
                $states[$userId]['genre'] = strlen($text) < 3 ? 'Unknown' : $text;
                $states[$userId]['step'] = 'playlist';
                file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                sendMessage($chatId, "📁 Введите название плейлиста.\n💡 Или пропустите: /skipstep\n💡 Отмена: /cancel");
                exit;
            case 'playlist':
                $states[$userId]['music_playlist'] = $text ?: 'Общая';
                $states[$userId]['step'] = 'awaiting_photo';
                file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                sendMessage($chatId, "🖼️ Отлично! Теперь отправьте изображение — это будет обложка трека.\n💡 Или пропустите: /skipphoto\n💡 Отменить: /cancel");
                exit;
            default:
                sendMessage($chatId, "❌ Неизвестный шаг. Напишите /start, чтобы начать сначала.");
                unset($states[$userId]);
                file_put_contents($stateFile, json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                exit;
        }
    }

    // === Нет состояния → команда или ошибка ===
    if (!isset($states[$userId])) {
        handleCommand($chatId, $text, $db, $token, $config);
        exit;
    }
}

// === Функции ===
function handleCommand($chatId, $text, $db, $token, $config) {
    $playerUrl = $config['webapp_url'] ?? 'https://tmh.tcse-cms.com/tmh/player.html';
    $baseUrl = $config['base_url'] ?? 'https://tmh.tcse-cms.com/tmh';

    switch ($text) {
        case '/start':
        case '/help':
            $keyboard = json_encode([
                'inline_keyboard' => [[
                    ['text' => '🎧 Запустить плеер', 'web_app' => ['url' => $playerUrl]]
                ]]
            ]);
            $msg = "🎵 Добро пожаловать! Используйте плеер для прослушивания.";
            sendMessage($chatId, $msg, false, $keyboard);
            break;
        case '/my_tracks':
        case '/top':
        case '/top_users':
        case '/top_genre':
        case '/playlist':
        case '/get_m3u':
        case '/get_pls':
        case '/get_xspf':
            sendMessage($chatId, "❌ У вас нет прав на эту команду.");
            break;
        default:
            sendMessage($chatId, "Отправьте аудио или используйте команды: /my_tracks, /top");
    }
}

function approveTrack($fileId, $adminChatId, $config, &$db, $dbFile) {
    if (!isset($db[$fileId])) {
        sendMessage($adminChatId, "❌ Трек не найден.");
        return;
    }
    if ($db[$fileId]['status'] !== 'pending') {
        sendMessage($adminChatId, "✅ Уже одобрен.");
        return;
    }
    $db[$fileId]['status'] = 'approved';
    file_put_contents($dbFile, json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $uploaderId = $db[$fileId]['user_uploader'];
    sendMessage($uploaderId, "✅ Ваш трек «{$db[$fileId]['title']}» одобрен и добавлен в плейлист!");
    sendMessage($adminChatId, "✅ Трек одобрен.");
}

function rejectTrack($fileId, $adminChatId, $config, &$db, $dbFile) {
    if (!isset($db[$fileId])) {
        sendMessage($adminChatId, "❌ Трек не найден.");
        return;
    }
    $title = $db[$fileId]['title'];
    $uploaderId = $db[$fileId]['user_uploader'];
    unset($db[$fileId]);
    file_put_contents($dbFile, json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    sendMessage($uploaderId, "❌ Ваш трек «$title» отклонён модератором.");
    sendMessage($adminChatId, "❌ Трек отклонён и удалён.");
}

function sendAudioWithCover($chatId, $fileId, $title, $performer, $photoFileId, $incrementPlay = false) {
    global $token, $dbFile;
    $data = [
        'chat_id' => $chatId,
        'audio' => $fileId,
        'title' => $title,
        'performer' => $performer
    ];
    if ($photoFileId) {
        $data['thumbnail'] = $photoFileId;
    }
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE)
        ]
    ]);
    file_get_contents("https://api.telegram.org/bot$token/sendAudio", false, $context);
    if ($incrementPlay) {
        $db = json_decode(file_get_contents($dbFile), true);
        if (isset($db[$fileId])) {
            $db[$fileId]['count_play'] = ($db[$fileId]['count_play'] ?? 0) + 1;
            file_put_contents($dbFile, json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }
}

function addChannelMusic($adminChatId, $config, &$db, $dbFile) {
    $channel = $config['channel'] ?? [];
    $musicFile = $channel['music_channel_file'] ?? __DIR__ . '/music_channel.json';

    if (!file_exists($musicFile)) {
        sendMessage($adminChatId, "❌ Файл music_channel.json не найден.");
        return;
    }

    $pending = array_filter(json_decode(file_get_contents($musicFile), true), fn($t) => $t['status'] === 'pending');
    if (empty($pending)) {
        sendMessage($adminChatId, "✅ Все треки из канала уже добавлены.");
        return;
    }

    $newCount = 0;
    foreach ($pending as $fileId => $track) {
        if (!isset($db[$fileId])) {
            $db[$fileId] = [
                'title' => $track['title'],
                'performer' => $track['performer'],
                'genre' => 'Channel Upload',
                'music_playlist' => 'Канал',
                'url' => $track['url'],
                'duration' => $track['duration'],
                'file_id' => $fileId,
                'user_uploader' => $adminChatId,
                'date_uploaded' => date('Y-m-d H:i:s'),
                'count_play' => 0,
                'count_downloads' => 0,
                'tags' => [],
                'status' => 'approved',
                'photo_file_id' => '',
                'photo_url' => ''
            ];
            $newCount++;
        }
        // Отмечаем как добавленный
        $pending[$fileId]['status'] = 'imported';
    }

    file_put_contents($dbFile, json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    file_put_contents($musicFile, json_encode($pending, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    sendMessage($adminChatId, "✅ Добавлено $newCount треков из канала.");
}

function sendMessage($chatId, $text, $markdown = false, $replyMarkup = null) {
    global $token;
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => $markdown ? 'Markdown' : null,
        'reply_markup' => $replyMarkup
    ];
    $data = array_filter($data, fn($v) => $v !== null);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE)
        ]
    ]);
    file_get_contents("https://api.telegram.org/bot$token/sendMessage", false, $context);
}

// === Обработка постов из канала ===
function handleChannelPost($post, $config, $botToken) {
    $channel = $config['channel'] ?? [];

    // Проверяем, что пост из нужного канала
    if ($post['chat']['username'] !== $channel['channel_username']) {
        return;
    }

    $postId = $post['message_id'];
    $postsFile = $channel['posts_file'];
    $musicFile = $channel['music_channel_file'];
    $adminChatIds = $config['moderation']['admin_chat_ids'] ?? [];

    // === 1. Сохраняем пост в блог ===
    if ($channel['enable_blog']) {
        $posts = file_exists($postsFile) ? json_decode(file_get_contents($postsFile), true) : [];

        $groupId = $post['media_group_id'] ?? null;

        // === Это часть медиаальбома ===
        if ($groupId) {
            // Ищем существующую запись с этим groupId
            $existingPost = null;
            $existingPostId = null;

            foreach ($posts as $id => $p) {
                if (($p['media_group_id'] ?? null) === $groupId) {
                    $existingPost = $p;
                    $existingPostId = $id;
                    break;
                }
            }

            if ($existingPost) {
                // Добавляем фото к существующей записи
                if (!isset($existingPost['photos'])) {
                    $existingPost['photos'] = [];
                }

                if (!empty($post['photo'])) {
                    $photo = end($post['photo']);
                    $fileInfo = json_decode(file_get_contents("https://api.telegram.org/bot$botToken/getFile?file_id={$photo['file_id']}"), true);
                    if ($fileInfo['ok']) {
                        $existingPost['photos'][] = [
                            'file_id' => $photo['file_id']
                        ];
                    }
                }

                // Если у основной записи нет текста/описания — возьмём из текущего поста
                if (empty($existingPost['caption']) && !empty($post['caption'])) {
                    $existingPost['caption'] = $post['caption'];
                }
                if (empty($existingPost['text']) && !empty($post['text'])) {
                    $existingPost['text'] = $post['text'];
                }

                // Обновляем запись
                $posts[$existingPostId] = $existingPost;
            } else {
                // Создаём новую запись
                $posts[$postId] = [
                    'id' => $postId,
                    'date' => date('Y-m-d H:i:s', $post['date']),
                    'text' => $post['text'] ?? '',
                    'caption' => $post['caption'] ?? '',
                    'media_group_id' => $groupId,
                    'photos' => []
                ];

                if (!empty($post['photo'])) {
                    $photo = end($post['photo']);
                    $fileInfo = json_decode(file_get_contents("https://api.telegram.org/bot$botToken/getFile?file_id={$photo['file_id']}"), true);
                    if ($fileInfo['ok']) {
                        $posts[$postId]['photos'][] = [
                            'file_id' => $photo['file_id']
                        ];
                    }
                }
            }
        } 
        // === Одиночное сообщение (не альбом) ===
        else {
            if (!isset($posts[$postId])) {
                $posts[$postId] = [
                    'id' => $postId,
                    'date' => date('Y-m-d H:i:s', $post['date']),
                    'text' => $post['text'] ?? '',
                    'caption' => $post['caption'] ?? '',
                    'photo_file_id' => null,
                    'audio' => null
                ];

                if (!empty($post['photo'])) {
                    $photo = end($post['photo']);
                    $fileInfo = json_decode(file_get_contents("https://api.telegram.org/bot$botToken/getFile?file_id={$photo['file_id']}"), true);
                    if ($fileInfo['ok']) {
                        $posts[$postId]['photo_file_id'] = $photo['file_id'];
                    }
                }
            }
        }

        file_put_contents($postsFile, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    // === 2. Обработка аудио для синхронизации ===
    if ($channel['enable_music_sync'] && !empty($post['audio'])) {
        $audio = $post['audio'];
        $fileId = $audio['file_id'];

        $music = file_exists($musicFile) ? json_decode(file_get_contents($musicFile), true) : [];

        if (!isset($music[$fileId])) {
            if ($channel['require_caption_for_audio'] && empty($post['caption'])) {
                return;
            }

            $fileInfo = json_decode(file_get_contents("https://api.telegram.org/bot$botToken/getFile?file_id=$fileId"), true);
            if (!$fileInfo['ok']) return;

            $filePath = $fileInfo['result']['file_path'];
            $url = "https://api.telegram.org/file/bot$botToken/$filePath";

            $music[$fileId] = [
                'file_id' => $fileId,
                'title' => $audio['title'] ?? ($post['caption'] ?? 'Без названия'),
                'performer' => $audio['performer'] ?? 'Неизвестный',
                'duration' => $audio['duration'],
                'url' => $url,
                'post_id' => $postId,
                'date_posted' => date('Y-m-d H:i:s', $post['date']),
                'caption' => $post['caption'] ?? '',
                'status' => $channel['auto_approve_audio'] ? 'approved' : 'pending'
            ];

            file_put_contents($musicFile, json_encode($music, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            // === ✅ ДОБАВЛЯЕМ audio В ПОСТ ===
            if (isset($posts[$postId])) {
                $posts[$postId]['audio'] = [
                    'file_id' => $fileId,
                    'title' => $audio['title'] ?? ($post['caption'] ?? 'Аудио'),
                    'performer' => $audio['performer'] ?? 'Автор не указан',
                    'duration' => $audio['duration']
                ];
                // === ✅ ОБНОВЛЯЕМ posts.json ===
                file_put_contents($postsFile, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }

            // === Уведомление админу ===
            $msg = "🎵 Новый трек в канале:\n";
            $msg .= "🎶 «{$music[$fileId]['title']}» — {$music[$fileId]['performer']}\n";
            $msg .= "📅 {$music[$fileId]['date_posted']}\n";
            $msg .= "📥 /add_channel_music — добавить в основную базу";

            foreach ($adminChatIds as $adminId) {
                sendMessage($adminId, $msg);
            }
        }
    }
}