import { preferences } from './ui-preferences';

const track = document.querySelector('[data-background-music]');
const positionKey = 'pgaals-background-music-position-v1';

if (track) {
    let positionRestored = false;
    let restorePending = false;
    let lastPositionSave = 0;
    track.loop = true;
    track.preload = 'auto';
    track.volume = 0.22;
    preferences.register(track);

    const restorePosition = () => {
        if (positionRestored) return;
        positionRestored = true;
        try {
            const position = Number(localStorage.getItem(positionKey));
            if (Number.isFinite(position) && position >= 0) {
                track.currentTime = track.duration > 0 ? Math.min(position, Math.max(0, track.duration - 0.1)) : position;
            }
        } catch {
            // Playback still works when browser storage is unavailable.
        }
    };

    const savePosition = (force = false) => {
        const now = Date.now();
        if (!force && now - lastPositionSave < 500) return;
        lastPositionSave = now;
        try { localStorage.setItem(positionKey, String(track.currentTime || 0)); } catch {}
    };

    const play = () => {
        if (!preferences.sound || document.hidden) return;
        if (!positionRestored && track.readyState < 1) {
            if (!restorePending) {
                restorePending = true;
                track.addEventListener('loadedmetadata', () => {
                    restorePending = false;
                    restorePosition();
                    play();
                }, { once: true });
            }
            return;
        }
        restorePosition();
        track.play().catch(() => {
            // Browsers may require a user gesture before allowing background audio.
        });
    };

    const pause = () => {
        track.pause();
    };

    const sync = event => {
        if (event?.detail?.sound === false || !preferences.sound) {
            savePosition(true);
            track.pause();
            return;
        }

        track.muted = false;
        play();
    };

    preferences.apply();
    track.addEventListener('loadedmetadata', restorePosition, { once: true });
    if (track.readyState >= 1) restorePosition();
    track.addEventListener('timeupdate', () => savePosition());
    play();
    ['pointerdown', 'keydown', 'touchstart'].forEach(eventName => {
        window.addEventListener(eventName, play, { passive: true });
    });
    window.addEventListener('pgaals:preferences', sync);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            savePosition(true);
            pause();
        }
        else play();
    });
    window.addEventListener('blur', () => { savePosition(true); pause(); });
    window.addEventListener('focus', play);
    window.addEventListener('pageshow', play);
    window.addEventListener('pagehide', () => { savePosition(true); pause(); });
    window.addEventListener('beforeunload', () => { savePosition(true); });
}
