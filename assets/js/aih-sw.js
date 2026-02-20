/**
 * Art in Heaven - Service Worker for Push Notifications
 */

self.addEventListener('install', function(event) {
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function(event) {
    if (!event.data) {
        return;
    }

    var data;
    try {
        data = event.data.json();
    } catch (e) {
        return;
    }

    var options = {
        body: data.body || '',
        icon: data.icon || '',
        tag: data.tag || 'aih-notification',
        data: {
            url: data.url || '/',
            art_piece_id: data.art_piece_id || null
        },
        requireInteraction: true
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'Art in Heaven', options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    var url = event.notification.data && event.notification.data.url ? event.notification.data.url : '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            // Focus existing tab if one is open on our site
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url.indexOf(url) !== -1 && 'focus' in client) {
                    return client.focus();
                }
            }
            // Otherwise open a new tab
            if (self.clients.openWindow) {
                return self.clients.openWindow(url);
            }
        })
    );
});
