import './bootstrap';
import '../css/fonts.css';
import '../css/campus.css';
import '../css/workspaces.css';
import '../css/login.css';
import '../css/frog-pond.css';
import '../css/fishing-sea.css';
import '../css/student-assessment.css';
import '../css/avatar-wardrobe.css';
import '../css/practice.css';
import '../css/ui-polish.css';
import '../css/data-tables.css';
import '../css/phil-iri.css';
import './phil-iri';
import './ml-prediction';
import './teacher-ml-level';
import '../css/ml-prediction.css';
import '../css/worksheets.css';
import '../css/numeracy-games.css';
import '../css/sidebar-navigation.css';
import '../css/app-footer.css';
import '../css/teacher-photo.css';
import '../css/student-tutor.css';
import '../css/teacher-ai-assistant.css';
import '../css/dark-mode.css';
import '../css/treasure-quest.css';
import '../css/answer-key.css';
import '../css/answer-feedback.css';
import './worksheet-book';
import { initFrogJump } from './frog-jump';
import comfortControls, { preferences } from './ui-preferences';
import './page-transitions';

import Alpine from 'alpinejs';
import studentCompanion from './student-companion';
import avatarWardrobe from './avatar-wardrobe';
import teacherPhotoUpload from './teacher-photo-upload';
import studentTutor from './student-tutor';
import assessmentWordHelp from './assessment-word-help';
import teacherAiAssistant from './teacher-ai-assistant';
import answerFeedback from './answer-feedback';

window.Alpine = Alpine;

Alpine.data('studentCompanion', studentCompanion);
Alpine.data('avatarWardrobe', avatarWardrobe);
Alpine.data('comfortControls', comfortControls);
Alpine.data('teacherPhotoUpload', teacherPhotoUpload);
Alpine.data('studentTutor', studentTutor);
Alpine.data('assessmentWordHelp', assessmentWordHelp);
Alpine.data('teacherAiAssistant', teacherAiAssistant);
Alpine.data('answerFeedback', answerFeedback);

Alpine.start();

const frogPond = document.getElementById('frog-pond-scene');
if (frogPond) {
    initFrogJump(frogPond.closest('.frog-pond-game'));
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
const treasureScene = document.getElementById('treasure-scene');
if (treasureScene) {
    import('./treasure-quest').then(({ initTreasureQuest }) => initTreasureQuest(treasureScene)).catch(() => {
        treasureScene.closest('.treasure-quest').dataset.sceneState = 'fallback';
        treasureScene.replaceChildren();
    });
}

buttonClickSound.preload = 'auto';
buttonClickSound.volume = 0.45;
preferences.register(buttonClickSound);

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
    if (!preferences.sound || !shouldPlayButtonClick(event.target)) {
        return;
    }

    buttonClickSound.currentTime = 0;
    buttonClickSound.play().catch(() => {});
}, { capture: true });
