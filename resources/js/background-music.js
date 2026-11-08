import { preferences } from './ui-preferences';

const track = document.querySelector('[data-background-music]');

if (track) {
    track.loop = true;
    track.preload = 'auto';
    track.volume = 0.22;
    preferences.register(track);

    const play = () => {
        if (!preferences.sound || document.hidden) return;
        track.play().catch(() => {
            // Browsers may require a user gesture before allowing background audio.
        });
    };

    const sync = event => {
        if (event?.detail?.sound === false || !preferences.sound) {
            track.pause();
            track.currentTime = 0;
            return;
        }

        track.muted = false;
        play();
    };

    preferences.apply();
    play();
    ['pointerdown', 'keydown', 'touchstart'].forEach(eventName => {
        window.addEventListener(eventName, play, { passive: true });
    });
    window.addEventListener('pgaals:preferences', sync);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) track.pause();
        else play();
    });
}
