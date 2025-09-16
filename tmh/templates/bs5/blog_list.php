<!-- Анонсы постов -->
<div class="row g-4">
<?php foreach ($paginatedPosts as $item): 
    // === Заголовок: первая строка caption или текста ===
    $title = '';
    if (!empty($item['caption'])) {
        $title = trim(explode("\n", $item['caption'])[0]);
    } else {
        $textLines = explode("\n", strip_tags($item['text']));
        $title = trim($textLines[0]) ?: 'Запись без названия';
    }
    $title = htmlspecialchars(mb_strlen($title) > 60 ? mb_substr($title, 0, 60) . '...' : $title);


    // === Текст для анонса: text или caption без заголовка ===
$rawText = $item['text'] ?? '';
if (empty($rawText) && !empty($item['caption'])) {
    $captionLines = explode("\n", $item['caption']);
    array_shift($captionLines); // удаляем первую строку (уже в заголовке)
    $rawText = implode("\n", $captionLines);
}

// Обрезаем до N символов (из config.php)
$maxExcerptLength = $channel['excerpt_length'] ?? 150;
$excerpt = mb_strlen(strip_tags($rawText)) > $maxExcerptLength 
    ? mb_substr(strip_tags($rawText), 0, $maxExcerptLength) . '...' 
    : strip_tags($rawText);

// Экранируем для вывода
$excerpt = htmlspecialchars($excerpt);

    // === Дата ===
    $date = date('d.m.Y', strtotime($item['date']));

    // === URL записи ===
    $url = 'blog/post/' . $item['id'] . '.html';

    // === Определение обложки: чёткий приоритет ===
    $coverUrl = '';

    // 1. Фото из Telegram (высший приоритет)
    if (!empty($item['photos']) && is_array($item['photos'])) {
        $firstPhoto = $item['photos'][0]['file_id'];
        $coverUrl = 'core/blog_cover.php?file_id=' . urlencode($firstPhoto);
    } elseif (!empty($item['photo_file_id'])) {
        $coverUrl = 'core/blog_cover.php?file_id=' . urlencode($item['photo_file_id']);
    }

    // 2. Нет фото → пробуем YouTube
    if (empty($coverUrl)) {
        $sourceText = $item['text'] ?? '';
        if (empty($sourceText) && !empty($item['caption'])) {
            $sourceText = $item['caption'];
        }
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $sourceText, $matches);
        if (!empty($matches[1])) {
            $videoId = $matches[1];
            $coverUrl = 'https://img.youtube.com/vi/' . $videoId . '/0.jpg';
        }
    }

    // 3. Нет фото и YouTube → проверяем тип медиа
    if (empty($coverUrl)) {
        if (!empty($item['audio']['file_id'])) {
            $coverUrl = 'https://placehold.co/400x400/121212/ffffff?text=audio';
        } elseif (!empty($item['video']['file_id'])) {
            $coverUrl = 'https://placehold.co/400x400/3498db/ffffff?text=video';
        } elseif (!empty($item['document']['file_id'])) {
            $coverUrl = 'https://placehold.co/400x400/e74c3c/ffffff?text=document';
        }
    }

    // 4. Ничего не подошло → общая заглушка
    if (empty($coverUrl)) {
        $coverUrl = 'https://placehold.co/400x400/9b59b6/ffffff?text=no+image';
    }

    // Убедимся, что нет лишних пробелов
    $coverUrl = trim($coverUrl);

    // === Определяем, нужно ли добавлять иконку "Play" ===
    $hasYouTube = !empty($matches[1]) && empty($item['photo_file_id']) && empty($item['photos']);

?>
    <div class="col-md-6 col-lg-4">
        <article itemscope itemtype="https://schema.org/BlogPosting" class="card h-100">
            
            <div class="position-relative">
                <a href="<?= $url ?>" class="text-decoration-none">
                    <img 
                        src="<?= $coverUrl ?>" 
                        alt="<?= $title ?>" 
                        class="card-img-top" 
                        itemprop="image" 
                        loading="lazy"
                    >
                    <?php if ($hasYouTube): ?>
                        <div class="youtube-play-overlay">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="currentColor" class="bi bi-play-circle-fill" viewBox="0 0 16 16">
                                <circle cx="8" cy="8" r="8" fill="#ff0000"/>
                                <path d="M11.596 8.596a.5.5 0 0 1 0 .708l-5 5a.5.5 0 0 1-.708-.708L10.293 8.5 6.293 4.404a.5.5 0 0 1 .708-.708l5 5z" fill="white"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                </a>
            </div>

            <div class="card-body d-flex flex-column">
                <h3 class="card-title" itemprop="headline">
                    <a href="<?= $url ?>" class="text-decoration-none text-success"><?= $title ?></a>
                </h3>
                <p class="card-text text-muted flex-grow-1" itemprop="description">
                    <?= $excerpt ?>
                </p>
                <small class="text-muted">
                    <time datetime="<?= $item['date'] ?>" itemprop="datePublished">
                        <?= $date ?>
                    </time>
                </small>
            </div>
            <meta itemprop="author" content="@<?= $channel['channel_username'] ?>">
            <link itemprop="mainEntityOfPage" href="<?= $config['base_url'] ?>/tmh/blog/post/<?= $item['id'] ?>.html">
        </article>
    </div>
<?php endforeach; ?>
</div>

<!-- Пагинация -->
<?php if (!$postId && $totalPages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): 
            $active = $i == $page ? ' active' : '';
            $href = $i === 1 ? 'blog/' : "blog/page/$i/";
        ?>
            <li class="page-item<?= $active ?>"><a class="page-link" href="<?= $href ?>"><?= $i ?></a></li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>