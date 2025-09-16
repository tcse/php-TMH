<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= $config['base_url'] ?>/">

    <!-- Title -->
    <title><?= htmlspecialchars($title) ?></title>

    <!-- Description -->
    <meta name="description" content="<?= htmlspecialchars($description) ?>">

    <!-- Open Graph -->
    <?php if ($pageType === 'list'): ?>
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?= $canonicalUrl ?>">
        <meta property="og:title" content="<?= htmlspecialchars($channel['blog_title'] ?? 'Блог') ?>">
        <meta property="og:description" content="<?= htmlspecialchars($channel['site_description'] ?? '') ?>">
        <meta property="og:image" content="<?= $config['base_url'] ?>/assets/logo.jpg">
    <?php elseif ($pageType === 'post' && $post): ?>
        <meta property="og:type" content="article">
        <meta property="og:url" content="<?= $canonicalUrl ?>">
        <meta property="og:title" content="<?= htmlspecialchars($title) ?>">
        <meta property="og:description" content="<?= htmlspecialchars($description) ?>">

        <?php
        // === OG Image: приоритет: фото > медиа > заглушка ===
        $ogImage = '';

        // 1. Фото из поста (одиночное или галерея)
        if (!empty($post['photo_file_id'])) {
            $ogImage = $config['base_url'] . '/core/blog_cover.php?file_id=' . urlencode($post['photo_file_id']);
        } elseif (!empty($post['photos']) && is_array($post['photos'])) {
            $firstPhoto = $post['photos'][0]['file_id'];
            $ogImage = $config['base_url'] . '/core/blog_cover.php?file_id=' . urlencode($firstPhoto);
        }
        // 2. Аудио
        elseif (!empty($post['audio']['file_id'])) {
            $ogImage = $config['base_url'] . '/assets/placeholders/audio.jpg';
        }
        // 3. Видео
        elseif (!empty($post['video']['file_id'])) {
            $ogImage = $config['base_url'] . '/assets/placeholders/video.jpg';
        }
        // 4. Документ
        elseif (!empty($post['document']['file_id'])) {
            $ogImage = $config['base_url'] . '/assets/placeholders/document.jpg';
        }
        // 5. Нет ничего — общая заглушка
        else {
            $ogImage = $config['base_url'] . '/assets/placeholders/no-image.jpg';
        }

        // Убедимся, что нет пробелов
        $ogImage = trim($ogImage);
        ?>

        <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
        <meta property="og:image:width" content="600">
        <meta property="og:image:height" content="315">
        <meta property="og:image:alt" content="<?= htmlspecialchars($title) ?>">
    <?php endif; ?>

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($description) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage ?? '') ?>">

    <!-- Schema.org -->
    <?php if ($pageType === 'post' && $post): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": <?= json_encode($title, JSON_UNESCAPED_UNICODE) ?>,
        "description": <?= json_encode($description, JSON_UNESCAPED_UNICODE) ?>,
        "image": <?= json_encode($ogImage, JSON_UNESCAPED_UNICODE) ?>,
        "author": {
            "@type": "Person",
            "name": "@<?= $channel['channel_username'] ?>"
        },
        "publisher": {
            "@type": "Organization",
            "name": "@<?= $channel['channel_username'] ?>",
            "logo": {
                "@type": "ImageObject",
                "url": "<?= $config['base_url'] ?>/assets/logo.jpg"
            }
        },
        "datePublished": "<?= date('c', strtotime($post['date'])) ?>",
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "<?= $canonicalUrl ?>"
        }
    }
    </script>
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" href="https://placehold.co/32x32/121212/1DB954?text=🎵" type="image/png">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">


    <!-- Стили -->
    <style>
        :root {
            --bg: #121212;
            --text: #e0e0e0;
            --border: #333;
            --accent: #1DB954;
        }

        [data-theme="light"] {
            --bg: #f8f9fa;
            --text: #212529;
            --border: #dee2e6;
            --accent: #198754;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            padding-top: 70px;
            transition: background-color 0.3s, color 0.3s;
        }

        .header {
            background-color: #121212;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1DB954;
        }

        .logo i {
            margin-right: 8px;
        }

        .footer {
            background-color: #121212;
            color: #888;
            text-align: center;
            padding: 1rem 0;
            font-size: 0.9rem;
        }

        .text-muted {
            color: var(--text) !important;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s;
            background: var(--bg);
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        .theme-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .swiper {
            width: 100%;
            height: 300px;
            border-radius: 12px;
            overflow: hidden;
        }

        .swiper-slide img {
            width: 100%;
            height: 70vh;
            object-fit: cover;
            cursor: grab;
        }

        .swiper-slide img:active {
            cursor: grabbing;
        }

        .gallery-title {
            font-size: 1.1rem;
            margin: 1rem 0 0.5rem 0;
            color: var(--accent);
        }

        .youtube-video {
            margin: 1.5rem 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            max-width: 640px;
        }

        .articleBody pre {
		    background: #1e1e1e;
		    padding: 1rem;
		    border-radius: 8px;
		    overflow-x: auto;
		    margin: 1rem 0;
		}
		.articleBody code {
		    background: #333;
		    padding: 0.2rem 0.4rem;
		    border-radius: 4px;
		    font-family: 'Courier New', monospace;
		}

        /* Overlay для YouTube */
        .youtube-play-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.4));
            animation: pulse 1.5s infinite ease-in-out;
        }

        .youtube-play-overlay svg {
            transition: transform 0.2s;
        }

        .youtube-play-overlay:hover svg {
            transform: scale(1.1);
        }

        @keyframes pulse {
            0% { opacity: 0.8; }
            50% { opacity: 1; }
            100% { opacity: 0.8; }
        }

    </style>
</head>
</head>