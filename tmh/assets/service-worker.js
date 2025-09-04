// TMH by TCSE v0.9.0
// service-worker.js — сервис-воркер для PWA
// Версия: 1.1 — с централизованной конфигурацией

// === Загрузка конфигурации через внешний PHP-файл ===
// Так как Service Worker работает в изолированной среде,
// мы не можем напрямую использовать PHP. Вместо этого:
// 1. Создаём `sw-config.php`, который возвращает конфигурацию
// 2. Загружаем её асинхронно при установке

const CONFIG_URL = '/plugins/tcse/tmh/sw-config.php';
const CACHE_NAME = 'tmh-player-v3';
let urlsToCache = [
    '/',
    '/tmh/player.html',
    '/tmh/assets/app.js',
    '/tmh/assets/styles.css',
    '/tmh/assets/img/logo_192.jpg',
    '/tmh/assets/img/logo_512.jpg',
    '/tmh/core/manifest.php'
];

// === Получение динамической конфигурации ===
async function loadConfig() {
    try {
        const response = await fetch(CONFIG_URL);
        if (response.ok) {
            const config = await response.json();
            const baseUrl = config.base_url;

            // Добавляем ключевые URL из конфига
            urlsToCache.push(
                `${baseUrl}/core/proxy.php`,
                `${baseUrl}/core/update_play.php`,
                `${baseUrl}/core/stream.php`,
                `${baseUrl}/core/cover.php`
            );
        }
    } catch (error) {
        console.warn('❌ Не удалось загрузить конфигурацию для SW:', error);
        // Продолжаем с базовым набором
    }
}

// === Установка: кэшируем ресурсы ===
self.addEventListener('install', async (event) => {
    console.log('🔧 Service Worker: установка...');
    await loadConfig();

    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(async (cache) => {
                const cachePromises = urlsToCache.map(url => {
                    return fetch(url)
                        .then(res => {
                            if (res.ok) {
                                return cache.put(url, res.clone());
                            }
                        })
                        .catch(err => {
                            console.warn(`⚠️ Не удалось закэшировать: ${url}`, err);
                        });
                });
                await Promise.all(cachePromises);
                console.log('✅ Service Worker: ресурсы закэшированы');
            })
            .catch(err => console.error('❌ Ошибка кэширования:', err))
    );
});

// === Активация: очистка старого кэша ===
self.addEventListener('activate', (event) => {
    console.log('🔧 Service Worker: активация...');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.filter(name => name !== CACHE_NAME)
                          .map(name => caches.delete(name))
            );
        }).then(() => {
            console.log('✅ Service Worker: старые кэши удалены');
        })
    );
});

// === Захват запросов ===
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Кэшируем только нужные типы ресурсов
    if (['script', 'style', 'image', 'document'].includes(request.destination)) {
        event.respondWith(
            caches.match(request)
                .then(response => {
                    return response || fetch(request);
                })
        );
    }
    // Для API-запросов — пропускаем без кэширования
    else if (url.pathname.includes('core/proxy.php') || 
             url.pathname.includes('core/update_play.php')) {
        event.respondWith(fetch(request));
    }
    // Все остальное — по умолчанию
    else {
        event.respondWith(fetch(request));
    }
});