<?php
// /tmh/core/generate_rss.php
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=1800');

$configFile = __DIR__ . '/../data/config.php';
if (!file_exists($configFile)) {
    die('<?xml version="1.0" encoding="UTF-8"?><error>Config not found</error>');
}

$config = require_once $configFile;
$channel = $config['channel'] ?? [];
$rss = $config['rss'] ?? [];

// === Если RSS отключён ===
if (!($rss['enable'] ?? true)) {
    http_response_code(404);
    echo '<?xml version="1.0" encoding="UTF-8"?><error>RSS disabled</error>';
    exit;
}

// === Параметры из config ===
$feedTitle = $rss['title'] ?? ($channel['blog_title'] ?? 'Блог проекта');
$feedDescription = $rss['description'] ?? ($channel['site_description'] ?? 'Обновления из Telegram');
$feedLink = $rss['link'] ?? ($config['webapp_url'] ?? rtrim($config['base_url'], '/') . '/blog.html');
$feedUrl = $rss['feed_url'] ?? rtrim($config['base_url'], '/') . '/core/generate_rss.php';
$maxItems = $rss['max_items'] ?? 20;
$includePhotos = $rss['include_photos'] ?? true;
$includeAudio = $rss['include_audio'] ?? false;
$showFullText = $rss['show_full_text'] ?? true;
$language = $rss['language'] ?? 'ru-RU';
$updatePeriod = $rss['update_period'] ?? 'hourly';
$generator = $rss['generator'] ?? 'TMH by TCSE';

$postsFile = $channel['posts_file'] ?? __DIR__ . '/../data/posts.json';

if (!file_exists($postsFile)) {
    echo '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"></rss>';
    exit;
}

$posts = json_decode(file_get_contents($postsFile), true);
if (!is_array($posts)) {
    echo '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"></rss>';
    exit;
}

usort($posts, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
$posts = array_slice($posts, 0, $maxItems);

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/">
<channel>
    <title><![CDATA[<?= htmlspecialchars($feedTitle, ENT_XML1) ?>]]></title>
    <link><?= htmlspecialchars($feedLink, ENT_XML1) ?></link>
    <description><![CDATA[<?= htmlspecialchars($feedDescription, ENT_XML1) ?>]]></description>
    <language><?= htmlspecialchars($language, ENT_XML1) ?></language>
    <pubDate><?= date(DATE_RSS) ?></pubDate>
    <generator><?= htmlspecialchars($generator, ENT_XML1) ?></generator>
    <sy:updatePeriod><?= htmlspecialchars($updatePeriod, ENT_XML1) ?></sy:updatePeriod>
    <atom:link href="<?= htmlspecialchars($feedUrl, ENT_XML1) ?>" rel="self" type="application/rss+xml" />

    <?php foreach ($posts as $post):
        $postId = $post['id'];
        $title = trim(strip_tags($post['caption'] ?? $post['text'] ?? 'Запись'));
        $title = mb_strlen($title) > 100 ? mb_substr($title, 0, 100) . '...' : $title;

        $fullText = '';
        if ($showFullText) {
            $fullText = ($post['text'] ?? '') . "\n\n" . ($post['caption'] ?? '');
        } else {
            $fullText = mb_substr(($post['text'] ?? '') . ($post['caption'] ?? ''), 0, 300) . '...';
        }
        $fullText = nl2br(htmlspecialchars($fullText, ENT_XML1));

        $postUrl = "$feedLink#post=$postId";
        $pubDate = date(DATE_RSS, strtotime($post['date']));

        $imageTag = '';
        $enclosureUrl = '';
        $baseUrl = rtrim($config['base_url'], '/');
        if ($includePhotos && !empty($post['photo_file_id'])) {
            $imageUrl = "$baseUrl/core/blog_cover.php?file_id=" . urlencode($post['photo_file_id']);
            $imageTag = "<p><img src=\"$imageUrl\" alt=\"Изображение\" style=\"max-width:100%;height:auto;\" /></p>";
            $enclosureUrl = $imageUrl;
        }

        $audioTag = '';
        if ($includeAudio && !empty($post['audio']['file_id'])) {
            $audioUrl = "$baseUrl/core/stream.php?id=" . urlencode($post['audio']['file_id']);
            $audioTag = "<p><audio controls src=\"$audioUrl\"></audio></p>";
            $enclosureUrl = $audioUrl; // Переопределяем enclosure
        }
    ?>
    <item>
        <title><![CDATA[<?= $title ?>]]></title>
        <link><?= htmlspecialchars($postUrl, ENT_XML1) ?></link>
        <guid isPermaLink="true"><?= htmlspecialchars($postUrl, ENT_XML1) ?></guid>
        <pubDate><?= $pubDate ?></pubDate>
        <description><![CDATA[
            <?= $imageTag ?>
            <p><?= $fullText ?></p>
            <?= $audioTag ?>
            <p><a href="<?= htmlspecialchars($postUrl, ENT_XML1) ?>">Читать далее</a></p>
        ]]></description>
        <?php if ($enclosureUrl): ?>
        <enclosure url="<?= htmlspecialchars($enclosureUrl, ENT_XML1) ?>" length="0" type="<?= $includeAudio ? 'audio/mpeg' : 'image/jpeg' ?>" />
        <?php endif; ?>
    </item>
    <?php endforeach; ?>
</channel>
</rss>