export const backgroundMusicPositionKey = 'pgaals-background-music-position-v2';

export function readBackgroundMusicPosition(storages, legacyKey = 'pgaals-background-music-position-v1') {
    for (const storage of storages) {
        for (const key of [backgroundMusicPositionKey, legacyKey]) {
            try {
                const saved = storage.getItem(key);
                if (saved === null || saved === '') continue;
                const position = Number(saved);
                if (Number.isFinite(position) && position >= 0) return position;
            } catch {
                // Try the next available browser storage.
            }
        }
    }

    return 0;
}

export function writeBackgroundMusicPosition(storages, position) {
    const normalized = Number(position);
    if (!Number.isFinite(normalized) || normalized < 0) return;

    for (const storage of storages) {
        try { storage.setItem(backgroundMusicPositionKey, String(normalized)); } catch {
            // Playback remains available when a browser blocks storage.
        }
    }
}
