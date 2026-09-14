import '../css/page-transitions.css';

// Older browsers still use normal navigation, with a short arrival animation.
if (!('CSSViewTransitionRule' in window)) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const showPage = () => {
        if (reducedMotion.matches) return;

        document.querySelector('main')?.animate?.(
            [
                { opacity: 0.45, transform: 'translateY(6px)' },
                { opacity: 1, transform: 'translateY(0)' },
            ],
            { duration: 260, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' },
        );
    };

    showPage();
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) showPage();
    });
}
