# 🎵 php-TMH — Telegram Music Hub (by TCSE)

**Telegram Music Hub** — это open-source система, позволяющая **управлять музыкой и контентом через Telegram-бота**, а отображать его на сайте.
![Демо php-TMH: блог и плеер](https://blogs.smartzone.ru/uploads/posts/2025-09/1757001507_photo_2025-09-04_18-56-28.jpg)
> 🔗 [Демо: tmh.tcse-cms.com](https://tmh.tcse-cms.com)  
> 💬 [Telegram-канал: @chuyakov_project](https://t.me/chuyakov_project)

---

# 🎵 TMH Blog (PHP Edition)  
### Автоматический блог на чистом PHP, основанный на постах из Telegram

> ⚡️ Лёгкий, быстрый, без баз данных. Подходит для хостингов с ограниченными правами.  
> 🔗 [github.com/tcse/php-TMH/tree/blog-php](https://github.com/tcse/php-TMH/tree/blog-php)

---

## ✅ Описание

Это **самостоятельная PHP-реализация блога**, которая:
- Читает данные из JSON (`posts.json`)
- Генерирует красивые страницы без JS
- Поддерживает SPA-подобное поведение
- Автоматически показывает фото, аудио, видео, документы
- Полностью адаптивна и SEO-friendly
- Работает на любом хостинге с PHP 7.4+

Идеально подходит для артистов, музыкантов, писателей, которые ведут канал в Telegram и хотят **автоматически публиковать контент на сайте**.

---

## 🌐 Структура проекта

```
/tmh/
├── blog.php                 ← Главный контроллер
├── .htaccess                ← Красивые URL: /blog/post/252.html
│
├── templates/
│   ├── base_01_head.php     ← <head>, метатеги, Open Graph
│   ├── base_02_header.php   ← Шапка сайта + меню
│   ├── base_03_main.php     ← <main> → подключает содержимое
│   ├── base_04_footer.php   ← Подвал сайта
│   ├── base_05_bottom.php   ← Скрипты + </body></html>
│   │
│   ├── blog_list.php        ← Карточки постов
│   ├── blog_post.php        ← Полная запись
│   └── tag.php              ← Страница тега (#music)
│
├── core/
│   ├── channel_proxy.php    ← API для получения постов
│   ├── blog_cover.php       ← Безопасный доступ к фото из Telegram
│   └── config.php           ← Конфигурация
│
├── data/
│   ├── posts.json           ← Данные о постах (генерируется ботом)
│   └── config.php           ← Настройки блога (название, пагинация и т.д.)
│
└── assets/
    └── placeholders/        ← Заглушки: audio.jpg, video.jpg, no-image.jpg
```

---

## 🔧 Центральная конфигурация

### `/data/config.php` — все настройки в одном месте

```php
return [
    'channel' => [
        'blog_title'       => 'Блог артиста',
        'site_description' => 'Официальный блог Chuyakov Project',
        'channel_username' => 'chuyakov_project',
        'posts_per_page'   => 10,
        'excerpt_length'   => 150,
        'accent_color'     => '#1DB954',
        'background_color' => '#121212',
        'text_color'       => '#e0e0e0'
    ]
];
```

> 💡 Измените здесь название, количество постов, цвета — и всё обновится.

---

## 🖼 Отображение контента

Блог умело определяет тип контента и показывает соответствующую обложку:

| Тип | Обложка |
|-----|--------|
| Фото из поста | Прямая загрузка через `blog_cover.php` |
| YouTube-ссылка | `https://img.youtube.com/vi/{ID}/0.jpg` |
| Аудио | Заглушка `audio.jpg` |
| Видео | Заглушка `video.jpg` |
| Документ | Заглушка `document.jpg` |
| Ничего нет | Общая заглушка `no-image.jpg` |

---

## ✍️ Форматирование текста из Telegram

Блог корректно отображает:
- ✅ `*жирный*` → `<strong>`
- ✅ `_курсив_` → `<em>`
- ✅ `` `моноширинный` `` → `<code>`
- ✅ ```код``` → `<pre><code>`
- ✅ `[текст](ссылка)` → кликабельная ссылка
- ✅ `#хештег` → фильтр: `/blog/tag/#music.html`
- ✅ `@username` → ссылка: `https://t.me/username`
- ✅ Ссылки на YouTube → автоматически встраивается как `<iframe>`

---

## 🔗 Красивые URL (SEO-friendly)

С помощью `.htaccess` реализованы человекочитаемые адреса:

| Было | Стало |
|------|-------|
| `blog.php?p=post/252` | `/blog/post/252.html` |
| `blog.php?page=2` | `/blog/page/2/` |

Поддерживается пагинация и навигация без перезагрузки (SPA-режим).

---

## 📱 Адаптивность и PWA

- ✅ Bootstrap 5 — карточки на всех экранах
- ✅ Тема «светлая/тёмная» с сохранением в `localStorage`
- ✅ Swiper.js — галереи из нескольких фото
- ✅ Schema.org + Open Graph — идеально для Telegram, WhatsApp, SEO

---

## 🧩 Микроразметка

Каждый пост содержит:
- `itemscope itemtype="https://schema.org/BlogPosting"`
- `og:image`, `og:title`, `og:description`
- `twitter:card`, `twitter:image`

Превью в мессенджерах выглядит **идеально**.

---

## 🏗 Архитектура шаблонов

Файлы названы так, чтобы **даже новичок понял порядок рендера**:

```
base_01_head.php      → <head> + метатеги
base_02_header.php    → шапка сайта
base_03_main.php      → подключает blog_list или blog_post
base_04_footer.php    → подвал
base_05_bottom.php    → скрипты + закрытие тегов
```

👉 Это позволяет легко:
- Создавать новые темы: `/templates/theme-dark/`, `/templates/glass-morphism/`
- Переключаться между ними через `config.php`

---

## 🛠️ Как установить

1. Склонируйте ветку:
   ```bash
   git clone -b blog-php https://github.com/tcse/php-TMH.git tmh
   ```
2. Убедитесь, что `data/posts.json` существует
3. Настройте `data/config.php`
4. Проверьте права на запись в `/tmh/assets/covers/`
5. Готово! Откройте: `https://ваш-сайт.ru/tmh/blog/`

---

## 🔄 Будущие возможности

- [ ] Поддержка `/blog/tag/#tag.html`
- [ ] Фильтрация по дате, типу медиа
- [ ] Локализация (RU/EN)
- [ ] RSS-лента
- [ ] Поиск по блогу

---

## 🙌 Благодарности

Разработано в рамках проекта **[TMH by TCSE](https://t.me/tmh_by_tcse_bot)**  
Автор: [@TCSEcmscom](https://t.me/TCSEcmscom)

---

## 📄 Лицензия

MIT — свободное использование, модификация, распространение.

---

> 💬 Есть вопросы? Пишите в Telegram: [@TCSEcmscom](https://t.me/TCSEcmscom)  
> 🐞 Нашли баг? Создайте Issue на GitHub


