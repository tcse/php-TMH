<main class="flex-grow-1">
    <div class="container py-5">
        <?php if ($pageType === 'post' && $post): ?>
            <?php include __DIR__ . '/blog_post.php'; ?>
        <?php else: ?>
            <?php include __DIR__ . '/blog_list.php'; ?>
        <?php endif; ?>
    </div>
</main>