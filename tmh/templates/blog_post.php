<div class="py-3">
    <!-- Назад -->
    <a href="blog/" class="btn btn-outline-secondary btn-sm">← Назад к списку</a>
</div>

<article itemscope itemtype="https://schema.org/BlogPosting">
    <header class="mb-4">
        <h1 itemprop="headline"><?= htmlspecialchars($title) ?></h1>
        <time datetime="<?= $post['date'] ?>" itemprop="datePublished" class="text-muted">
            <?= date('d.m.Y H:i', strtotime($post['date'])) ?>
        </time>
    </header>

    <!-- Галерея или фото -->
    <?php if (!empty($post['photos'])): ?>
        <div class="swiper-container mb-4">
            <div class="swiper-wrapper">
                <?php foreach ($post['photos'] as $photo): ?>
                    <div class="swiper-slide">
                        <img src="core/blog_cover.php?file_id=<?= urlencode($photo['file_id']) ?>"
                             alt="Фото" class="w-100 rounded">
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    <?php elseif (!empty($post['photo_file_id'])): ?>
        <img src="core/blog_cover.php?file_id=<?= urlencode($post['photo_file_id']) ?>"
             alt="Изображение" class="w-100 rounded mb-4" itemprop="image">
    <?php endif; ?>

    <!-- Текст статьи -->
    <?php
    $fullText = '';
    if (!empty($post['text'])) {
        $fullText = $post['text'];
    } elseif (!empty($post['caption'])) {
        // Удаляем первую строку (уже в заголовке)
        $captionLines = explode("\n", $post['caption']);
        array_shift($captionLines);
        $fullText = implode("\n", array_map('trim', $captionLines));
    }

    // Делаем ссылки кликабельными
    $fullText = preg_replace(
        '/(https?:\/\/[^\s]+)/',
        '<a href="$1" target="_blank" rel="noopener" style="color:#1DB954;">$1</a>',
        htmlspecialchars($fullText)
    );
    $fullText = nl2br(trim($fullText));
    ?>
    <?php if (!empty($fullText)): ?>
        <div itemprop="articleBody" class="mb-4">
            <?= $fullText ?>
        </div>
    <?php endif; ?>

    <!-- Аудио -->
    <?php if (!empty($post['audio']['file_id'])): 
        $audioTitle = $post['audio']['title'] ?? '';
        $firstLineOfTitle = explode("\n", $audioTitle)[0] ?? 'Аудиозапись';
    ?>
        <div class="mb-4">
            <h4>🎧 Аудио</h4>
            <p><strong><?= htmlspecialchars($post['audio']['performer'] ?? '') ?> — <?= htmlspecialchars($firstLineOfTitle) ?></strong></p>
            <audio controls style="width:100%;" preload="metadata">
                <source src="core/stream.php?id=<?= urlencode($post['audio']['file_id']) ?>" type="audio/mpeg">
                Ваш браузер не поддерживает аудио.
            </audio>
        </div>
    <?php endif; ?>

    <!-- Ссылка на Telegram -->
    <a href="https://t.me/<?= $channel['channel_username'] ?>/<?= $post['id'] ?>" 
       class="btn btn-outline-primary mt-3" target="_blank" rel="noopener">
        Перейти в Telegram
    </a>
</article>