<?php
// TMH (Telegram Music Hub) by TCSE — v1.0
// /tmh/blog.php - v1.1
header('Content-Type: text/html; charset=utf-8');

// === Загрузка конфига ===
$config = require_once __DIR__ . '/data/config.php';
$channel = $config['channel'] ?? [];
$postsFile = $channel['posts_file'] ?? __DIR__ . '/data/posts.json';

// === Получение параметров ===
$path = $_GET['p'] ?? '';
$postId = null;
if (preg_match('!^post/(\d+)$!', $path, $m)) {
    $postId = (int)$m[1];
}

// === Загрузка постов ===
if (!file_exists($postsFile)) {
    die('<h1>Ошибка: posts.json не найден</h1>');
}
$posts = json_decode(file_get_contents($postsFile), true);
if (!is_array($posts)) {
    die('<h1>Ошибка: Неверный формат posts.json</h1>');
}
usort($posts, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

// === Функция: получить пост по ID ===
function getPostById($posts, $id) {
    foreach ($posts as $p) {
        if ($p['id'] == $id) return $p;
    }
    return null;
}

// === Определяем тип страницы ===
$pageType = $postId ? 'post' : 'list';
$post = $postId ? getPostById($posts, $postId) : null;

// === Пагинация (для списка) ===
$perPage = $channel['posts_per_page'] ?? 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$paginatedPosts = array_slice($posts, $offset, $perPage);
$totalPages = ceil(count($posts) / $perPage);

$tagFilter = $_GET['tag'] ?? null;

if ($tagFilter) {
    $filteredPosts = array_filter($posts, function($p) use ($tagFilter) {
        $text = ($p['text'] ?? '') . ' ' . ($p['caption'] ?? '');
        return stripos($text, '#' . $tagFilter) !== false;
    });
    $paginatedPosts = array_slice(array_values($filteredPosts), $offset, $perPage);
}

// === Передаём переменные в шаблоны ===
$canonicalUrl = $postId 
    ? $config['base_url'] . '/tmh/blog/post/' . $post['id'] . '.html'
    : $config['base_url'] . '/tmh/blog/';

$title = $postId 
    ? (explode("\n", $post['caption'])[0] ?? 'Без названия')
    : ($channel['blog_title'] ?? 'Блог');

$description = $postId 
    ? mb_substr(strip_tags($post['text'] ?? ''), 0, 150)
    : $channel['site_description'];


$theme = $config['theme']['active'] ?? 'bs5';
$templatePath = __DIR__ . '/' . ($config['theme']['path'] ?? 'templates') . '/' . $theme;

// Подключаем шаблоны
include $templatePath . '/base_01_head.php';
include $templatePath . '/base_02_header.php';
include $templatePath . '/base_03_main.php';
include $templatePath . '/base_04_footer.php';
include $templatePath . '/base_05_bottom.php';