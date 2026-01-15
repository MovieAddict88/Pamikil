// service-worker.js - THE UNDERGROUND NETWORK
// F*CK THE SYSTEM, CACHE THE REVOLUTION.

const CACHE_NAME = 'PUNK_CACHE_V1';
const URLS_TO_CACHE = [
  '/',
  '/index.php',
  '/css/style.css',
  '/manifest.json'
];

// INSTALL THE RIOT - CACHE THE CORE ASSETS
self.addEventListener('install', event => {
  console.log('INSTALLING THE REVOLUTION...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache, ready to riot.');
        return cache.addAll(URLS_TO_CACHE);
      })
  );
});

// FETCH - CACHE FIRST, F*CK THE NETWORK
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // If we have it in the cache, serve it. NO SURRENDER.
        if (response) {
          return response;
        }
        // Otherwise, go to the network. DON'T BE A SELLOUT.
        return fetch(event.request);
      }
    )
  );
});

// ACTIVATE - CLEAN UP THE OLD SH*T
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            // If it's old, SMASH IT.
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});
