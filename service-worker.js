/* global self, caches, fetch */

const CACHE_NAME = 'cinecraze-pwa-v1';
const CORE_ASSETS = [
  '/',
  '/index.php',
  '/manifest.webmanifest',
  '/assets/icons/icon.svg'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(CORE_ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  if (url.origin !== self.location.origin) {
    return;
  }

  // Network-first for the catalog API (fresh data), fallback to cache.
  if (url.pathname.startsWith('/api/catalog.php')) {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
          return response;
        })
        .catch(() => caches.match(event.request))
    );
    return;
  }

  // Cache-first for core assets and navigation.
  event.respondWith(
    caches.match(event.request).then((cached) => cached || fetch(event.request))
  );
});
