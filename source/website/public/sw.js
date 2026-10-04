var CACHE_STATIC_NAME = 'static-v27';

self.addEventListener('install', function(event) {
    event.waitUntil(
      caches.open(CACHE_STATIC_NAME)
        .then(function(cache) {
          console.log('[Service Worker] Precaching App Shell');
          return cache.addAll([
            './offline',
            './assets/front/images/offline.png',
            './assets/front/img/static/offline-breadcrumb.jpeg'
          ]);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    console.log('[Service Worker] Activating Service Worker ....', event);
    event.waitUntil(
      caches.keys()
        .then(function(keyList) {
          return Promise.all(keyList.map(function(key) {
            if (key !== CACHE_STATIC_NAME) {
              return caches.delete(key);
            }
          }));
        })
    );
    return self.clients.claim();
});

self.addEventListener('fetch', function(event) {
    // Never turn failed form/API mutations into an HTML "offline" response.
    // Let the browser/application receive the real network error instead.
    if (event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
      caches.match(event.request)
        .then(function(response) {
          if (response) {
            return response;
          }

          return fetch(event.request).catch(function() {
            // The offline page is only valid for document navigation.
            if (event.request.mode !== 'navigate') {
              throw new Error('Network request failed');
            }

            return caches.open(CACHE_STATIC_NAME)
              .then(function(cache) {
                return cache.match('./offline');
              });
          });
        })
    );
});

self.addEventListener('push', function(e) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    if (e.data) {
        var msg = e.data.json();
        var options = { body: msg.body, icon: msg.icon };
        if (msg.actions && msg.actions.length > 0) {
            options.actions = msg.actions;
        }
        e.waitUntil(self.registration.showNotification(msg.title, options));
    }
});

self.addEventListener('notificationclick', function(e) {
    if (e.action.length > 0) {
        self.clients.openWindow(e.action);
    }
});
