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

    // === Определение обложки ===
    $coverUrl = '';

    // 1. Фото (одиночное или из галереи)
    if (!empty($item['photos']) && is_array($item['photos'])) {
        $firstPhoto = $item['photos'][0]['file_id'];
        $coverUrl = 'core/blog_cover.php?file_id=' . urlencode($firstPhoto);
    } elseif (!empty($item['photo_file_id'])) {
        $coverUrl = 'core/blog_cover.php?file_id=' . urlencode($item['photo_file_id']);
    }
    // 2. Нет фото → проверяем тип медиа
    elseif (!empty($item['audio']['file_id'])) {
        $coverUrl = 'https://placehold.co/400x400/121212/ffffff?text=audio';
    } elseif (!empty($item['video']['file_id'])) {
        $coverUrl = 'https://placehold.co/400x400/3498db/ffffff?text=video';
    } elseif (!empty($item['document']['file_id'])) {
        $coverUrl = 'https://placehold.co/400x400/e74c3c/ffffff?text=document';
    }
    // 3. Нет ничего → общая заглушка
    else {
        $coverUrl = 'https://placehold.co/400x400/9b59b6/ffffff?text=no+image';
    }

    // Убедимся, что нет лишних пробелов
    $coverUrl = trim($coverUrl);
?>
    <div class="col-md-6 col-lg-4">
        <article itemscope itemtype="https://schema.org/BlogPosting" class="card h-100">
            <img src="<?= $coverUrl ?>" alt="<?= $title ?>" class="card-img-top" itemprop="image" loading="lazy">
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