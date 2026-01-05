const CACHE_NAME = 'cinecraze-v1.0.0';
const urlsToCache = [
    '/',
    '/index.php',
    '/api.php',
    '/config.php',
    '/manifest.json',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://cdn.plyr.io/3.7.8/plyr.css',
    'https://cdn.plyr.io/3.7.8/plyr.js'
];

// Install event - cache resources
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Return cached version or fetch from network
                if (response) {
                    return response;
                }
                
                return fetch(event.request).then(response => {
                    // Don't cache non-successful responses
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }
                    
                    // Clone the response
                    const responseToCache = response.clone();
                    
                    caches.open(CACHE_NAME)
                        .then(cache => {
                            cache.put(event.request, responseToCache);
                        });
                    
                    return response;
                });
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Background sync for watch later
self.addEventListener('sync', event => {
    if (event.tag === 'sync-watchlater') {
        event.waitUntil(syncWatchLater());
    }
});

// Push notifications (future feature)
self.addEventListener('push', event => {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body,
            icon: '/icons/icon-192x192.png',
            badge: '/icons/icon-72x72.png',
            data: data.url,
            actions: [
                {
                    action: 'open',
                    title: 'Open App'
                },
                {
                    action: 'close',
                    title: 'Close'
                }
            ]
        };
        
        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

// Notification click handler
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    if (event.action === 'open') {
        event.waitUntil(
            clients.openWindow(event.notification.data || '/')
        );
    }
});

// Sync watch later items when back online
async function syncWatchLater() {
    try {
        const db = await openDB();
        const transaction = db.transaction(['watchlater'], 'readonly');
        const store = transaction.objectStore('watchlater');
        const items = await store.getAll();
        
        for (const item of items) {
            if (item.syncStatus === 'pending') {
                try {
                    const response = await fetch('/api.php?action=add_watchlater', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            user_id: item.user_id,
                            content_id: item.content_id,
                            episode_id: item.episode_id
                        })
                    });
                    
                    if (response.ok) {
                        // Mark as synced
                        const updateTransaction = db.transaction(['watchlater'], 'readwrite');
                        const updateStore = updateTransaction.objectStore('watchlater');
                        item.syncStatus = 'synced';
                        await updateStore.put(item);
                    }
                } catch (error) {
                    console.error('Failed to sync watch later item:', error);
                }
            }
        }
    } catch (error) {
        console.error('Failed to sync watch later:', error);
    }
}

// IndexedDB helper
function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('CineCrazeDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            if (!db.objectStoreNames.contains('content')) {
                const contentStore = db.createObjectStore('content', { keyPath: 'id' });
                contentStore.createIndex('type', 'type', { unique: false });
                contentStore.createIndex('year', 'year', { unique: false });
                contentStore.createIndex('title', 'title', { unique: false });
            }
            
            if (!db.objectStoreNames.contains('watchlater')) {
                const watchLaterStore = db.createObjectStore('watchlater', { keyPath: 'id', autoIncrement: true });
                watchLaterStore.createIndex('user_id', 'user_id', { unique: false });
                watchLaterStore.createIndex('content_id', 'content_id', { unique: false });
                watchLaterStore.createIndex('syncStatus', 'syncStatus', { unique: false });
            }
            
            if (!db.objectStoreNames.contains('settings')) {
                db.createObjectStore('settings', { keyPath: 'key' });
            }
        };
    });
}

// Periodic background sync (experimental)
self.addEventListener('periodicsync', event => {
    if (event.tag === 'content-sync') {
        event.waitUntil(checkForContentUpdates());
    }
});

async function checkForContentUpdates() {
    try {
        const response = await fetch('/api.php?action=get_last_update');
        const data = await response.json();
        
        if (data.success) {
            const lastUpdate = localStorage.getItem('last_content_update');
            if (lastUpdate !== data.last_update) {
                // Notify main app of new content
                const clients = await self.clients.matchAll();
                clients.forEach(client => {
                    client.postMessage({
                        type: 'CONTENT_UPDATE',
                        last_update: data.last_update
                    });
                });
            }
        }
    } catch (error) {
        console.error('Failed to check for content updates:', error);
    }
}