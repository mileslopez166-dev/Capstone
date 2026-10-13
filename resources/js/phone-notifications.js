const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

const urlBase64ToUint8Array = (value) => {
    const padding = '='.repeat((4 - (value.length % 4)) % 4);
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);

    return Uint8Array.from([...raw].map((character) => character.charCodeAt(0)));
};

const request = async (url, method, payload) => {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(payload),
    });

    if (!response.ok) {
        throw new Error(`Phone alert request failed (${response.status})`);
    }

    return response.json();
};

const setState = (button, state, detail) => {
    const label = button.querySelector('[data-phone-push-label]');
    const status = button.querySelector('[data-phone-push-status]');
    const icon = button.querySelector('[data-phone-push-icon]');
    const subscribed = state === 'subscribed';

    button.dataset.pushState = state;
    button.disabled = state === 'busy' || state === 'unsupported';
    label.textContent = subscribed ? 'Phone alerts enabled' : 'Enable phone alerts';
    status.textContent = detail;
    icon.textContent = subscribed ? 'notifications_active' : 'notifications_none';
};

const initializePhonePush = async (button) => {
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        setState(button, 'unsupported', 'Not supported on this browser or app.');
        return;
    }

    if (Notification.permission === 'denied') {
        setState(button, 'unsupported', 'Blocked in this device\'s notification settings.');
        return;
    }

    try {
        const registration = await navigator.serviceWorker.register('/push-worker.js', { scope: '/' });
        const current = await registration.pushManager.getSubscription();
        setState(
            button,
            current ? 'subscribed' : 'idle',
            current ? 'Tap to turn off alerts on this device.' : 'Get important alerts on this device.',
        );

        button.addEventListener('click', async () => {
            setState(button, 'busy', 'Updating phone alerts...');

            try {
                const subscription = await registration.pushManager.getSubscription();
                if (subscription) {
                    await request(button.dataset.unsubscribeUrl, 'DELETE', { endpoint: subscription.endpoint });
                    await subscription.unsubscribe();
                    setState(button, 'idle', 'Phone alerts are off on this device.');
                    return;
                }

                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    setState(button, 'unsupported', 'Allow notifications in this device\'s settings.');
                    return;
                }

                const created = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(button.dataset.publicKey),
                });
                const payload = created.toJSON();

                try {
                    await request(button.dataset.subscribeUrl, 'POST', {
                        endpoint: payload.endpoint,
                        keys: payload.keys,
                        content_encoding: PushManager.supportedContentEncodings?.[0] ?? 'aes128gcm',
                    });
                } catch (error) {
                    await created.unsubscribe();
                    throw error;
                }

                setState(button, 'subscribed', 'Tap to turn off alerts on this device.');
            } catch (error) {
                console.error(error);
                setState(button, 'idle', 'Could not update alerts. Please try again.');
            }
        });
    } catch (error) {
        console.error(error);
        setState(button, 'unsupported', 'Phone alerts could not start on this device.');
    }
};

document.querySelectorAll('[data-phone-push]').forEach(initializePhonePush);
