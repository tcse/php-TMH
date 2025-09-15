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
                             alt="Фото" class="img-fluid rounded">
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    <?php elseif (!empty($post['photo_file_id'])): ?>
        <img src="core/blog_cover.php?file_id=<?= urlencode($post['photo_file_id']) ?>"
             alt="Изображение" class="img-fluid rounded mb-4" itemprop="image">
    <?php endif; ?>

    <!-- Текст статьи с форматированием -->
    <?php
// === Функция: форматирование текста с entities и переносами строк ===
function applyEntities($text, $entities) {
    if (empty($text)) return '';

    // Если нет entities — просто вернём текст с переносами
    if (empty($entities)) {
        return nl2br(htmlspecialchars($text));
    }

    // Сортируем с конца, чтобы не сломать позиции при замене
    usort($entities, function($a, $b) {
        return $b['offset'] - $a['offset'];
    });

    // Массив для хранения фрагментов (текст + HTML)
    $fragments = [html_entity_decode($text)]; // Работаем с UTF-8 напрямую

    foreach ($entities as $entity) {
        $start = $entity['offset'];
        $len = $entity['length'];
        $end = $start + $len;

        if ($start < 0 || $end > strlen($text)) continue;

        $substr = substr($text, $start, $len);

        switch ($entity['type']) {
            case 'bold':
                $replacement = "<strong>" . htmlspecialchars($substr) . "</strong>";
                break;
            case 'italic':
                $replacement = "<em>" . htmlspecialchars($substr) . "</em>";
                break;
            case 'underline':
                $replacement = "<u>" . htmlspecialchars($substr) . "</u>";
                break;
            case 'strikethrough':
                $replacement = "<del>" . htmlspecialchars($substr) . "</del>";
                break;
            case 'code':
                $replacement = "<code>" . htmlspecialchars($substr) . "</code>";
                break;
            case 'pre':
                $language = isset($entity['language']) ? htmlspecialchars($entity['language']) : '';
                $replacement = "<pre><code class=\"language-$language\">" . htmlspecialchars($substr) . "</code></pre>";
                break;
            case 'text_link':
                $url = htmlspecialchars($entity['url']);
                $replacement = "<a href=\"$url\" target=\"_blank\" rel=\"noopener\">" . htmlspecialchars($substr) . "</a>";
                break;
            default:
                $replacement = htmlspecialchars($substr);
        }

        // Замена в оригинальной строке
        $text = substr_replace($text, $replacement, $start, $len);
    }

    // Теперь обрабатываем оставшиеся переносы строк
    return nl2br($text);
}

// === Получаем текст и entities ===
$sourceText = $post['text'] ?? '';
if (empty($sourceText) && !empty($post['caption'])) {
    $sourceText = $post['caption'];
}

$entities = $post['entities'] ?? [];
if (empty($sourceText) && !empty($post['caption_entities'])) {
    $entities = $post['caption_entities'];
}

// === Применяем форматирование ===
$formattedText = applyEntities($sourceText, $entities);

// === Обработка YouTube-ссылок → iframe ===
$formattedText = preg_replace_callback(
    '/(https?:\/\/(?:www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11}))/i',
    function ($matches) {
        $videoId = strlen($matches[3]) === 11 ? $matches[3] : $matches[4];
        $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
        return <<<HTML
<div class="mb-4 youtube-video">
    <div style="position:relative;padding-top:56.25%;">
        <iframe 
            style="position:absolute;top:0;left:0;width:100%;height:100%;border-radius:12px;"
            src="$embedUrl" 
            title="YouTube video player" 
            frameborder="0" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen>
        </iframe>
    </div>
</div>
HTML;
    },
    $formattedText
);

// === Хештеги и @username (после YouTube!) ===
$formattedText = preg_replace_callback(
    '/(^|\s)(#[^\s#]+)(?=$|\s)/u',
    function ($matches) {
        $tag = ltrim($matches[2], '#');
        return $matches[1] . "<a href=\"blog/tag/{$tag}.html\" style=\"color:#1DB954;\">{$matches[2]}</a>";
    },
    $formattedText
);
$formattedText = preg_replace_callback(
    '/(^|\s)(@[^\s@]+)(?=$|\s)/u',
    function ($matches) {
        $username = ltrim($matches[2], '@');
        return $matches[1] . "<a href=\"https://t.me/{$username}\" target=\"_blank\" style=\"color:#1DB954;\">{$matches[2]}</a>";
    },
    $formattedText
);
?>
<div itemprop="articleBody" class="mb-4">
    <?= $formattedText ?>
</div>
    

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

    <!-- Видео -->
    <?php if (!empty($post['video']['file_id'])): 
        $videoFileName = $post['video']['file_name'] ?? 'Видео.mp4';
        $proxyUrl = 'core/blog_cover.php?file_id=' . urlencode($post['video']['file_id']);
    ?>
        <div class="mb-4">
            <h4>📹 Видео</h4>
            <video controls style="width:100%; border-radius:8px;" poster="https://placehold.co/640x360/3498db/ffffff?text=VIDEO">
                <source src="<?= $proxyUrl ?>" type="video/mp4">
                Ваш браузер не поддерживает видео.
            </video>
            <p><small><a href="<?= $proxyUrl ?>" download="<?= urlencode($videoFileName) ?>">Скачать видео</a></small></p>
        </div>
    <?php endif; ?>

    <!-- Документ -->
    <?php if (!empty($post['document']['file_id'])): 
        $docFileName = $post['document']['file_name'] ?? 'Документ';
        $proxyUrl = 'core/blog_cover.php?file_id=' . urlencode($post['document']['file_id']);
    ?>
        <div class="mb-4">
            <h4>📎 Документ</h4>
            <p>
                <i class="bi bi-file-earmark"></i>
                <a href="<?= $proxyUrl ?>" download="<?= urlencode($docFileName) ?>">
                    <?= htmlspecialchars($docFileName) ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Ссылка на Telegram -->
    <a href="https://t.me/<?= $channel['channel_username'] ?>/<?= $post['id'] ?>" 
       class="btn btn-outline-primary mt-3" target="_blank" rel="noopener">
        Перейти в Telegram
    </a>
</article>