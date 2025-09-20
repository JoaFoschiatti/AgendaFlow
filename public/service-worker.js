// AgendaFlow Service Worker
const CACHE_NAME = 'agendaflow-v1.0.0';
const urlsToCache = [
  '/AgendaFlow/public/',
  '/AgendaFlow/public/css/responsive.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'
];

// Install event - cache resources
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Cache opened');
        return cache.addAll(urlsToCache);
      })
      .catch(error => {
        console.error('Failed to cache:', error);
      })
  );
  // Force the waiting service worker to become the active service worker
  self.skipWaiting();
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
  // Take control of all pages immediately
  self.clients.claim();
});

// Fetch event - serve from cache when possible
self.addEventListener('fetch', event => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Parse the URL
  const url = new URL(event.request.url);

  // Skip cross-origin requests
  if (url.origin !== location.origin && !url.href.startsWith('https://cdn.')) {
    return;
  }

  // Network first strategy for API calls
  if (url.pathname.includes('/api/') || url.pathname.includes('/login') || url.pathname.includes('/logout')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Clone the response before caching
          const responseToCache = response.clone();

          caches.open(CACHE_NAME)
            .then(cache => {
              cache.put(event.request, responseToCache);
            });

          return response;
        })
        .catch(() => {
          return caches.match(event.request);
        })
    );
    return;
  }

  // Cache first strategy for static assets
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }

        return fetch(event.request)
          .then(response => {
            // Check if we received a valid response
            if (!response || response.status !== 200 || response.type === 'opaque') {
              return response;
            }

            // Clone the response before caching
            const responseToCache = response.clone();

            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });

            return response;
          });
      })
      .catch(error => {
        console.error('Fetch failed:', error);
        // Return offline page if available
        if (event.request.destination === 'document') {
          return caches.match('/AgendaFlow/public/offline.html');
        }
      })
  );
});

// Push notification event
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'Tienes una nueva notificación',
    icon: '/AgendaFlow/public/icons/icon-192x192.png',
    badge: '/AgendaFlow/public/icons/icon-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    }
  };

  event.waitUntil(
    self.registration.showNotification('AgendaFlow', options)
  );
});

// Notification click event
self.addEventListener('notificationclick', event => {
  console.log('Notification click received.');
  event.notification.close();

  event.waitUntil(
    clients.openWindow('/AgendaFlow/public/dashboard')
  );
});

// Background sync event
self.addEventListener('sync', event => {
  if (event.tag === 'sync-appointments') {
    event.waitUntil(syncAppointments());
  }
});

async function syncAppointments() {
  try {
    // Implement background sync logic here
    console.log('Syncing appointments in background...');
  } catch (error) {
    console.error('Background sync failed:', error);
  }
}