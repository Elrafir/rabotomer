const CACHE_NAME = 'rabotomer-v3';
const ASSETS_TO_CACHE = [
  './',
  './manifest.json',
  './assets/css/tailwind.min.css',
  './assets/css/main.css',
  './assets/js/jquery.min.js',
  './assets/js/main.js',
  './assets/js/timer.js',
  './assets/js/tasks.js',
  './assets/js/offline-sync.js',
  './assets/js/chart.umd.js',
  './assets/js/analytics.js',
  './assets/js/timeline.js',
  './assets/img/clock-icon.svg',
  './assets/img/logo4.png'
];

// Установка SW и кэширование статики
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        return cache.addAll(ASSETS_TO_CACHE);
      })
      .then(() => self.skipWaiting())
  );
});

// Очистка старых кэшей
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Перехват запросов (Network First для HTML/API, Cache First для статики)
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  
  // Для API-запросов (AJAX) - только Network, кэш не трогаем, пусть падает
  // (тогда сработает логика offline-sync.js)
  if (url.pathname.includes('/api/') || url.pathname.includes('_ajax') || event.request.method !== 'GET') {
    return; // Пропускаем через обычный fetch
  }

  // Для ассетов (css, js, img) - Cache First
  if (url.pathname.startsWith('/assets/')) {
    event.respondWith(
      caches.match(event.request).then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }
        return fetch(event.request).then((networkResponse) => {
          return caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, networkResponse.clone());
            return networkResponse;
          });
        });
      })
    );
    return;
  }

  // Для навигации (HTML) - Network First
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).then((networkResponse) => {
        return caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, networkResponse.clone());
          return networkResponse;
        });
      }).catch(() => {
        // Если сети нет, отдаем из кэша
        return caches.match(event.request);
      })
    );
  }
});
