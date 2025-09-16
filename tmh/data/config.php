<?php
// TMH (Telegram Music Hub) by TCSE — v1.0
// config.php — централизованная конфигурация
// Важно: НЕ ДОБАВЛЯЙТЕ ПРОБЕЛЫ В КОНЦЕ СТРОК!

return [
    // === ОСНОВНЫЕ НАСТРОЙКИ ===

    'bot_token' => ' ',
    // Токен вашего Telegram-бота. Получить: @BotFather
    // ⚠️ Храните в безопасности! Не публикуйте в открытых репозиториях

    'base_url' => 'https://tmh.tcse-cms.com/tmh',
    // Базовый URL проекта. Должен указывать на корень папки /tmh/
    // Используется в ссылках: cover.php, stream.php, blog_cover.php и др.
    // ❗ Без слеша в конце

    'webapp_url' => 'https://tmh.tcse-cms.com/tmh/player.html',
    // URL веб-плеера. Используется в кнопке "Запустить плеер" и PWA

    'webhook_url' => 'https://tmh.tcse-cms.com/tmh/core/bot.php',
    // URL вебхука. Должен вести на bot.php
    // Устанавливается через set_webhook.php

    'secret_key' => 'mysecret123',
    // Секретный ключ для защищённых скриптов: cleanup.php, cleanup_blog.php
    // Пример вызова: cleanup.php?key=mysecret123

    'enable_logging' => true,
    // Включать ли логирование действий (true/false)
    // Логи пишутся в /data/logs/*.log

    'debug' => false,
    // Режим отладки. true — показывать ошибки PHP, false — скрывать
    // Рекомендуется false на продакшене

    // === ФАЙЛЫ И ПУТИ ===
    // Все пути относительно __DIR__ (/tmh/data/)

    'db_file' => __DIR__ . '/music_db.json',
    // Основная база данных треков. Содержит file_id, название, исполнителя, счётчики

    'state_file' => __DIR__ . '/user_states.json',
    // Хранит состояние загрузки трека (шаг, file_id, название и т.д.)

    'posts_file' => __DIR__ . '/posts.json',
    // Хранит посты из канала для блога

    'music_channel_file' => __DIR__ . '/music_channel.json',
    // Временная база для треков из канала (до добавления в основную)

    'log_dir' => __DIR__ . '/logs',
    // Папка для логов (stream.log, update_play.log, cleanup.log и др.)
    // Будет создана автоматически, если не существует

    'max_file_size_bytes' => 50 * 1024 * 1024,
    // Максимальный размер аудиофайла (в байтах). По умолчанию: 50 МБ

    // === МОДЕРАЦИЯ ===

    'moderation' => [
        'enable' => true,
        // Включена ли модерация (true) или все треки добавляются сразу (false)

        'admin_chat_ids' => ['757940529'],
        // Список chat_id администраторов, которые получают уведомления о новых загрузках

        'notify_admin_on_upload' => true,
        // Отправлять ли уведомление админу при новой загрузке

        'auto_approve_for' => ['757940529'],
        // Список chat_id, чьи треки добавляются без модерации (например, владелец)
    ],

    // === ПРАВА ДОСТУПА ===

    'permissions' => [
        'roles' => [
            'admin' => [
                'can_upload' => true,
                // Может ли загружать треки
                'can_moderate' => true,
                // Может ли одобрять/отклонять треки
                'can_see_stats' => true,
                // Может ли видеть /my_tracks, /top и т.д.
                'can_use_all_commands' => true,
                // Может ли использовать все команды
                'can_view_blog' => true
                // Может ли просматривать блог
            ],
            'trusted' => [
                'can_upload' => true,
                'can_moderate' => false,
                'can_see_stats' => true,
                'can_use_all_commands' => true,
                'can_view_blog' => true
            ],
            'guest' => [
                'can_upload' => false,
                'can_moderate' => false,
                'can_see_stats' => false,
                'can_use_all_commands' => false,
                'can_view_blog' => true
            ]
        ],
        'role_map' => [
            'admin' => ['757940529'],
            // Какие chat_id имеют роль 'admin'
            'trusted' => []
            // Какие chat_id имеют роль 'trusted'
        ]
    ],

    // === КАНАЛ И БЛОГ ===

    'channel' => [
        'channel_username' => 'chuyakov_project',
        // Имя канала (без @), откуда синхронизируется контент

        'enable_blog' => true,
        // Включить ли блог из постов канала

        'enable_music_sync' => true,
        // Включить ли синхронизацию аудио из канала

        'require_caption_for_audio' => false,
        // Требовать ли описание (caption) для аудио из канала

        'auto_approve_audio' => false,
        // Автоматически одобрять треки из канала (true) или отправлять на модерацию (false)

        'posts_file' => __DIR__ . '/posts.json',
        // Путь к файлу постов блога

        'music_channel_file' => __DIR__ . '/music_channel.json',
        // Путь к файлу треков из канала

        // === ВЕБ-ИНТЕРФЕЙС (внутри channel) ===
        'blog_title' => 'Второй Блог артиста',
        // Заголовок блога, используется в <title> и метатегах

        'site_description' => 'Сниппеты, демо, аранжировки — из Telegram на сайт',
        // Описание сайта для SEO и Open Graph

        'logo_url' => 'https://placehold.co/400x400/3498db/ffffff?text=TMG+by+TCSE',
        // Ссылка на логотип (рекомендуется 120x120)

        'posts_per_page' => 9,
        // Количество постов на странице блога

        'show_excerpts' => true,
        // Показывать краткий анонс текста поста

        'enable_comments' => false,
        // Включены ли комментарии (в будущих версиях)

        'gallery_autoplay' => true,
        // Автовоспроизведение фото в галерее

        'gallery_delay' => 5000
        // Задержка между слайдами в миллисекундах
    ],

    'theme' => [
        'active' => 'bs5',  // или 'theme2'
        'path'   => 'templates'
    ],

    // === СЧЁТЧИКИ ===

    'enable_play_count'       => true,   // Включить счётчик прослушиваний
    'enable_download_count'   => true,   // Включить счётчик скачиваний
    'play_count_debounce_sec' => 30,     // Минимальный интервал между учётом прослушивания (защита от флуда)

    // === ВЕБ-ИНТЕРФЕЙС (глобальные стили) ===

    'accent_color' => '#1DB954',         // Цвет акцента (например, зелёный Spotify)
    'background_color' => '#121212',     // Цвет фона (тёмный)
    'text_color' => '#e0e0e0',           // Цвет текста
    'border_color' => '#333',            // Цвет рамок и разделителей

    // === ПЛЕЕР ===

    'player' => [
        'autoplay' => false,             // Автовоспроизведение при открытии плеера
        'shuffle' => true,               // Включён ли режим перемешивания по умолчанию
        'default_volume' => 0.8,         // Громкость по умолчанию (0.0 — 1.0)
        'enable_cors' => true,           // Разрешать ли CORS-запросы к proxy.php и update_play.php
        'cache_ttl' => 3600,             // Время кэширования ответов в секундах
        'default_cover' => 'https://placehold.co/400x400/121212/ffffff?text=Music',
        // Заглушка для треков без обложки
        'enable_likes' => true,          // Показывать кнопку "Нравится"
        'enable_top_commands' => true    // Показывать команды /top, /my_tracks
    ],

    // === ПЛЕЙЛИСТЫ ===

    'playlist' => [
        'title' => 'Музыкальный плейлист',
        // Название плейлиста в M3U/PLS/XSPF
        'creator' => 'TMH by TCSE',
        // Автор плейлиста
        'name' => 'tmh_playlist'
        // Имя файла (без расширения)
    ],

    // === ССЫЛКИ ===

    'links' => [
        'telegram_channel' => 'https://t.me/chuyakov_project',
        'github_repo' => 'https://github.com/tcse/php-TMH'
    ],

    // === ПРОЧИЕ НАСТРОЙКИ ===

    'use_proxy_for_stream' => true,
    // Использовать stream.php как прокси для аудио (рекомендуется)

    'auto_update_file_urls' => true,
    // Автоматически обновлять URL файлов при изменении (например, после смены бота)

    'show_my_tracks' => true,
    // Показывать команду /my_tracks в боте

    'enable_top_commands' => true,
    // Показывать команды /top_played, /top_downloaded

    'enable_likes' => true,
    // Включить систему "Нравится"

    'enable_rss' => true,
    // Включить RSS-ленту блога (generate_rss.php)

    'rss_max_items' => 20,
    // Максимальное количество записей в RSS

    'include_photos_in_rss' => true,
    // Включать фото в RSS-ленту

    'include_audio_in_rss' => false
    // Включать аудио в RSS (может быть тяжело для ридеров)
];