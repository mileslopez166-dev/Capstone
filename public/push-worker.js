self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data?.json() ?? {};
    } catch {
        payload = { body: event.data?.text() ?? '' };
    }

    const title = payload.title || 'AI-PGAALS';
    delete payload.title;
    payload.icon ||= '/favicon.svg';
    payload.badge ||= '/favicon.svg';
    payload.data ||= { url: '/' };

    event.waitUntil(self.registration.showNotification(title, payload));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    let target = new URL(event.notification.data?.url || '/', self.location.origin);
    if (target.origin !== self.location.origin) {
        target = new URL('/', self.location.origin);
    }

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const existing = windows.find((client) => new URL(client.url).origin === target.origin);

        if (existing) {
            if ('navigate' in existing) {
                await existing.navigate(target.href);
            }
            return existing.focus();
        }

        return self.clients.openWindow(target.href);
    })());
});
