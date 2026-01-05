const CACHE_NAME = 'cinecraze-v1';
const urlsToCache = [
  '/index.php',
  '/assets/css/style.css',
  '/assets/js/app.js',
  '/manifest.json',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  'https://cdn.plyr.io/3.7.8/plyr.css',
  'https://cdn.plyr.io/3.7.8/plyr.js'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache.map(url => new Request(url, {credentials: 'same-origin'})));
      })
      .catch(error => {
        console.log('Cache install error:', error);
      })
  );
  self.skipWaiting();
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        
        return fetch(event.request).then(response => {
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }
          
          const responseToCache = response.clone();
          
          caches.open(CACHE_NAME)
            .then(cache => {
              cache.put(event.request, responseToCache);
            });
          
          return response;
        });
      })
      .catch(() => {
        return caches.match('/index.php');
      })
  );
});

self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'New content available!',
    icon: '/assets/images/icon-192.png',
    badge: '/assets/images/icon-72.png',
    vibrate: [200, 100, 200],
    tag: 'cinecraze-notification',
    requireInteraction: false
  };
  
  event.waitUntil(
    self.registration.showNotification('CineCraze', options)
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow('/')
  );
});
