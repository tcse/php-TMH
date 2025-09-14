<?php
// TMH (Telegram Music Hub) by TCSE — v1.0.1
// config.php — централизованная конфигурация
// Важно: НЕ ДОБАВЛЯЙТЕ ПРОБЕЛЫ В КОНЦЕ СТРОК!

return [
    // === ОСНОВНЫЕ НАСТРОЙКИ ===
    'bot_token' => '',
    // Токен вашего Telegram-бота. Получить: @BotFather

    'base_url' => 'https://tmh.tcse-cms.com/tmh',
    // Базовый URL вашего проекта. Должен указывать на папку /tmh/

    'webapp_url' => 'https://tmh.tcse-cms.com/tmh/player.html',
    // URL веб-плеера. Используется в кнопке "Запустить плеер"

    'webhook_url' => 'https://tmh.tcse-cms.com/tmh/core/bot.php',
    // URL вебхука. Должен вести на bot.php

    'secret_key' => 'mysecret123',
    // Секретный ключ для защищённых скриптов (cleanup.php, cleanup_blog.php)

    'enable_logging' => true,
    // Включать ли логирование действий (true/false)

    'debug' => false,
    // Режим отладки. true — показывать ошибки, false — скрывать

    // === ФАЙЛЫ И ПУТИ ===
    // Все пути относительно __DIR__ (/tmh/data/)
    'db_file' => __DIR__ . '/music_db.json',
    // Основная база данных треков

    'state_file' => __DIR__ . '/user_states.json',
    // Хранит состояние загрузки трека (шаг, file_id, название и т.д.)

    'posts_file' => __DIR__ . '/posts.json',
    // Хранит посты из канала для блога

    'music_channel_file' => __DIR__ . '/music_channel.json',
    // Временная база для треков из канала (до добавления в основную)

    'log_dir' => __DIR__ . '/logs',
    // Папка для логов (stream.log, cleanup.log и др.)

    'max_file_size_bytes' => 50 * 1024 * 1024,
    // Максимальный размер аудиофайла (50 МБ)

    // === МОДЕРАЦИЯ ===
    'moderation' => [
        'enable' => true,
        // Включена ли модерация (true) или все треки добавляются сразу (false)

        'admin_chat_ids' => ['757940529'],
        // Список chat_id администраторов, которые получают уведомления

        'notify_admin_on_upload' => true,
        // Отправлять ли уведомление админу при новой загрузке

        'auto_approve_for' => ['757940529'],
        // Список chat_id, чьи треки добавляются без модерации
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
    ],

    // === RSS ЛЕНТА ===
    'rss' => [
        'enable' => true,
        // Включена ли RSS-лента (можно отключить для скрытых проектов)

        'title' => $channel['blog_title'] ?? 'Музыкальный хаб',
        // Заголовок RSS-канала. По умолчанию — из blog_title

        'description' => $channel['site_description'] ?? 'Обновления из Telegram-канала',
        // Описание ленты

        'link' => $config['webapp_url'] ?? ($config['base_url'] . '/blog.html'),
        // Ссылка на сайт/блог

        'feed_url' => $config['base_url'] . '/core/generate_rss.php',
        // Публичный URL самой RSS-ленты

        'max_items' => 20,
        // Максимальное количество записей в ленте

        'include_photos' => true,
        // Включать ли фото в `<description>` и `<enclosure>`

        'include_audio' => false,
        // Включать ли аудио-файл как `<enclosure>` (может быть тяжело для ридеров)

        'show_full_text' => true,
        // Показывать полный текст поста или только анонс

        'language' => 'ru-RU',
        // Язык RSS-канала

        'update_period' => 'hourly',
        // Частота обновления (для <sy:updatePeriod>)
        // Возможные значения: always, hourly, daily, weekly, monthly, never

        'generator' => 'TMH by TCSE v1.0',
        // Имя генератора (видно в некоторых ридерах)
    ],

    // === СЧЁТЧИКИ ===
    'enable_play_count'       => true,   // Включить счётчик прослушиваний
    'enable_download_count'   => true,   // Включить счётчик скачиваний
    'play_count_debounce_sec' => 30,     // Минимальный интервал между учётом прослушивания (защита от флуда)
];
