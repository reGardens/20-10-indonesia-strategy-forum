// ISF Web Push Service Worker Handler
// Handles push notifications and notification interactions

self.addEventListener('push', function (event) {
    var payload = {};
    if (event.data) {
        try {
            payload = event.data.json();
        } catch (e) {
            payload = {
                title: 'Indonesia Strategy Forum',
                body: event.data.text()
            };
        }
    }

    var title = payload.title || 'Indonesia Strategy Forum';
    var options = {
        body: payload.body || 'Ada analisis dan kebijakan strategis terbaru untuk Anda.',
        icon: payload.icon || '/icons/icon-192x192.png',
        badge: payload.badge || '/icons/badge-72x72.png',
        vibrate: [100, 50, 100],
        tag: payload.tag || ('isf-notification-' + Date.now()),
        renotify: true,
        data: Object.assign({
            url: '/',
            timestamp: Date.now()
        }, payload.data || {}),
        actions: payload.actions || [
            { action: 'open', title: 'Baca Analisis' },
            { action: 'close', title: 'Tutup' }
        ]
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    if (event.action === 'close') {
        return;
    }

    var targetUrl = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

self.addEventListener('pushsubscriptionchange', function (event) {
    event.waitUntil(
        fetch('/api/isf/push/vapid-key')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.public_key) return;
                return self.registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlB64ToUint8Array(data.public_key)
                });
            })
            .then(function (subscription) {
                if (!subscription) return;
                var raw = subscription.toJSON();
                return fetch('/api/isf/push/subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        endpoint: subscription.endpoint,
                        keys: raw.keys,
                        content_encoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0]
                    })
                });
            })
    );
});

function urlB64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
    var rawData = atob(base64);
    var outputArray = new Uint8Array(rawData.length);
    for (var i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}
