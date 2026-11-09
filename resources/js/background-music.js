import { preferences } from './ui-preferences';
import {
    readBackgroundMusicPosition,
    writeBackgroundMusicPosition,
} from './background-music-state.js';

const track = document.querySelector('[data-background-music]');

if (track) {
    let positionRestored = false;
    let restorePending = false;
    let lastPositionSave = 0;
    const storages = [];
    try { storages.push(window.sessionStorage); } catch {}
    try { storages.push(window.localStorage); } catch {}

    track.autoplay = false;
    track.loop = true;
    track.preload = 'auto';
    track.volume = 0.22;
    preferences.register(track);

    const restorePosition = () => {
        if (positionRestored) return;
        const position = readBackgroundMusicPosition(storages);
        const duration = Number(track.duration);
        const maximum = Number.isFinite(duration) && duration > 0 ? Math.max(0, duration - 0.1) : position;
        try { track.currentTime = Math.min(position, maximum); } catch {}
        positionRestored = true;
    };

    const savePosition = (force = false) => {
        if (!positionRestored) return;
        const now = Date.now();
        if (!force && now - lastPositionSave < 500) return;
        lastPositionSave = now;
        writeBackgroundMusicPosition(storages, track.currentTime || 0);
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
    window.addEventListener('pageshow', play);
    window.addEventListener('pagehide', () => { savePosition(true); pause(); });
    window.addEventListener('beforeunload', () => { savePosition(true); });
}
