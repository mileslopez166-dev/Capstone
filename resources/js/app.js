import './bootstrap';
import '../css/fonts.css';
import '../css/campus.css';
import '../css/workspaces.css';
import '../css/login.css';
import '../css/frog-pond.css';
import '../css/fishing-sea.css';
import '../css/student-assessment.css';
import './page-transitions';

import Alpine from 'alpinejs';
import studentCompanion from './student-companion';

window.Alpine = Alpine;

Alpine.data('studentCompanion', studentCompanion);

Alpine.start();

const frogPond = document.getElementById('frog-pond-scene');
if (frogPond) {
    import('./frog-pond').then(({ initFrogPond }) => initFrogPond(frogPond)).catch(() => {
        const game = frogPond.closest('.frog-pond-game');
        game?.classList.remove('pond-3d-ready');
        game?.setAttribute('data-pond-state', 'fallback');
        frogPond.replaceChildren();
    });
}

const fishingSea = document.getElementById('fishing-sea-scene');
if (fishingSea) {
    import('./fishing-sea').then(({ initFishingSea }) => initFishingSea(fishingSea)).catch(() => {
        const game = fishingSea.closest('.hook-game');
        game?.classList.remove('sea-3d-ready');
        game?.setAttribute('data-sea-state', 'fallback');
        fishingSea.replaceChildren();
    });
}

const buttonClickSound = new Audio('/audio/button-click.mp3');
buttonClickSound.preload = 'auto';
buttonClickSound.volume = 0.45;

const shouldPlayButtonClick = (target) => {
    const buttonLike = target.closest('button, a, [role="button"], input[type="button"], input[type="submit"], input[type="reset"]');

    if (!buttonLike) {
        return false;
    }

    if (buttonLike.matches('[disabled], [aria-disabled="true"]')) {
        return false;
    }

    if (buttonLike.closest('[data-no-click-sound]')) {
        return false;
    }

    return true;
};

document.addEventListener('click', (event) => {
    if (!shouldPlayButtonClick(event.target)) {
        return;
    }

    buttonClickSound.currentTime = 0;
    buttonClickSound.play().catch(() => {});
}, { capture: true });
