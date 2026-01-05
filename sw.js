// CineCraze Service Worker
const CACHE_NAME = 'cinecraze-v1.0.0';
const urlsToCache = [
    '/',
    '/index.php',
    '/login.php',
    '/admin.php',
    '/manifest.json',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://cdn.plyr.io/3.7.8/plyr.css',
    'https://cdn.plyr.io/3.7.8/plyr.js'
];

// Install event - cache resources
self.addEventListener('install', function(event) {
    console.log('Service Worker: Installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('Service Worker: Caching files');
                return cache.addAll(urlsToCache);
            })
            .then(function() {
                console.log('Service Worker: Installation complete');
                return self.skipWaiting();
            })
            .catch(function(error) {
                console.log('Service Worker: Installation failed', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', function(event) {
    console.log('Service Worker: Activating...');
    
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Service Worker: Deleting old cache', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(function() {
            console.log('Service Worker: Activation complete');
            return self.clients.claim();
        })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', function(event) {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }
    
    // Skip API requests for dynamic content
    if (event.request.url.includes('/api/')) {
        return;
    }
    
    // Handle video requests differently
    if (event.request.url.includes('.mp4') || event.request.url.includes('.m3u8')) {
        event.respondWith(fetch(event.request));
        return;
    }
    
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // Return cached version or fetch from network
                if (response) {
                    console.log('Service Worker: Serving from cache', event.request.url);
                    return response;
                }
                
                console.log('Service Worker: Fetching from network', event.request.url);
                return fetch(event.request)
                    .then(function(response) {
                        // Don't cache if not a valid response
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }
                        
                        // Clone the response
                        const responseToCache = response.clone();
                        
                        // Add to cache for future use
                        caches.open(CACHE_NAME)
                            .then(function(cache) {
                                cache.put(event.request, responseToCache);
                            });
                        
                        return response;
                    })
                    .catch(function(error) {
                        console.log('Service Worker: Fetch failed', error);
                        
                        // Return offline page or basic response
                        if (event.request.destination === 'document') {
                            return caches.match('/offline.html') || new Response('Offline - Please check your connection');
                        }
                    });
            })
    );
});

// Background sync for offline actions
self.addEventListener('sync', function(event) {
    console.log('Service Worker: Background sync', event.tag);
    
    if (event.tag === 'watchlater-sync') {
        event.waitUntil(syncWatchLater());
    }
});

// Push notifications
self.addEventListener('push', function(event) {
    console.log('Service Worker: Push received', event);
    
    const options = {
        body: event.data ? event.data.text() : 'New content available!',
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'Explore Content',
                icon: '/icons/icon-192x192.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/icons/icon-192x192.png'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification('CineCraze', options)
    );
});

// Notification click handling
self.addEventListener('notificationclick', function(event) {
    console.log('Service Worker: Notification click received', event);
    
    event.notification.close();
    
    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

// Message handling from main thread
self.addEventListener('message', function(event) {
    console.log('Service Worker: Message received', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'GET_VERSION') {
        event.ports[0].postMessage({version: CACHE_NAME});
    }
});

// Sync watch later data when online
async function syncWatchLater() {
    try {
        console.log('Service Worker: Syncing watch later data');
        
        // Get offline watch later actions from IndexedDB
        const db = await openIndexedDB();
        const transaction = db.transaction(['pendingActions'], 'readonly');
        const store = transaction.objectStore('pendingActions');
        const actions = await getAllFromStore(store);
        
        for (const action of actions) {
            try {
                await fetch('/api/sync.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(action.data)
                });
                
                // Remove from pending if successful
                await removeFromStore(store, action.id);
                console.log('Service Worker: Synced action', action.id);
            } catch (error) {
                console.log('Service Worker: Failed to sync action', action.id, error);
            }
        }
        
    } catch (error) {
        console.log('Service Worker: Watch later sync failed', error);
    }
}

// IndexedDB helpers
function openIndexedDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('CineCrazeDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            // Create pending actions store for offline sync
            if (!db.objectStoreNames.contains('pendingActions')) {
                const store = db.createObjectStore('pendingActions', { keyPath: 'id' });
                store.createIndex('timestamp', 'timestamp', { unique: false });
            }
        };
    });
}

function getAllFromStore(store) {
    return new Promise((resolve, reject) => {
        const request = store.getAll();
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

function removeFromStore(store, id) {
    return new Promise((resolve, reject) => {
        const request = store.delete(id);
        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

// Periodic background sync (if supported)
if ('periodicSync' in self.registration) {
    self.addEventListener('periodicsync', function(event) {
        console.log('Service Worker: Periodic sync', event.tag);
        
        if (event.tag === 'content-sync') {
            event.waitUntil(syncContent());
        }
    });
}

async function syncContent() {
    try {
        console.log('Service Worker: Periodic content sync');
        
        const response = await fetch('/api/sync.php?action=sync');
        const data = await response.json();
        
        if (data.success) {
            // Cache the latest content data
            const cache = await caches.open(CACHE_NAME);
            await cache.put('/api/sync.php?action=sync', new Response(JSON.stringify(data)));
            console.log('Service Worker: Content synced');
        }
    } catch (error) {
        console.log('Service Worker: Content sync failed', error);
    }
}

console.log('Service Worker: Script loaded');