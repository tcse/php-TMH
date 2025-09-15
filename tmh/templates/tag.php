<!-- /tmh/templates/tag.php -->
<h1>Посты с тегом <?= htmlspecialchars($_GET['tag'] ?? '') ?></h1>
<div class="row g-4">
<?php
$tag = $_GET['tag'] ?? '';
if (!$tag) {
    echo '<p>Тег не указан.</p>';
    return;
}

$filteredPosts = array_filter($posts, function($p) use ($tag) {
    $text = ($p['text'] ?? '') . ' ' . ($p['caption'] ?? '');
    return stripos($text, '#' . $tag) !== false;
});

foreach ($filteredPosts as $item): 
    // ... копируйте логику из blog_list.php ...
?>
    <div class="col-md-6 col-lg-4">
        <article class="card h-100">
            <!-- ... карточка ... -->
        </article>
    </div>
<?php endforeach; ?>
</div>