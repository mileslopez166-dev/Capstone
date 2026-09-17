export default function studentCompanion(messages) {
    let timer;
    let observer;
    let motionPreference;
    let visibilityChanged;
    let motionChanged;

    return {
        messages,
        messageIndex: 0,
        displayText: messages[0],
        isSpeaking: false,
        isPaused: false,
        isVisible: false,
        reducedMotion: false,
        documentHidden: document.hidden,
        isMobile: false,
        compact: true,

        get message() {
            return this.messages[this.messageIndex];
        },

        get canAnimate() {
            return this.isVisible && !this.documentHidden && !this.isPaused && !this.reducedMotion && (!this.isMobile || !this.compact);
        },

        init() {
            this.isMobile = this.$el.classList.contains('campus-companion-mobile');
            try { this.compact = localStorage.getItem('pgaals-compact-companion') !== 'false'; } catch {}
            motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
            this.reducedMotion = window.PgaalsPreferences?.reducedMotion ?? motionPreference.matches;
            motionChanged = () => {
                this.reducedMotion = window.PgaalsPreferences?.reducedMotion ?? motionPreference.matches;
                this.syncPlayback();
            };
            visibilityChanged = () => {
                this.documentHidden = document.hidden;
                this.syncPlayback();
            };
            motionPreference.addEventListener('change', motionChanged);
            window.addEventListener('pgaals:preferences', motionChanged);
            document.addEventListener('visibilitychange', visibilityChanged);

            // Only the visible companion runs: desktop sidebar or mobile strip.
            observer = new IntersectionObserver(([entry]) => {
                this.isVisible = entry.isIntersecting;
                this.syncPlayback();
            });
            observer.observe(this.$el);
        },

        stop() {
            clearTimeout(timer);
            this.isSpeaking = false;
        },

        syncPlayback() {
            this.stop();
            if (!this.canAnimate) {
                this.displayText = this.message;
                return;
            }

            const letters = Array.from(this.message);
            let position = 0;
            this.displayText = '';
            this.isSpeaking = true;

            const typeLetter = () => {
                this.displayText += letters[position++];
                if (position === letters.length) {
                    this.isSpeaking = false;
                    timer = setTimeout(() => this.nextMessage(), 5500);
                    return;
                }
                const punctuation = /[!?. ,]/.test(letters[position - 1]);
                timer = setTimeout(typeLetter, punctuation ? 95 : 45);
            };
            timer = setTimeout(typeLetter, 180);
        },

        nextMessage() {
            this.messageIndex = (this.messageIndex + 1) % this.messages.length;
            this.syncPlayback();
        },

        togglePause() {
            this.isPaused = !this.isPaused;
            this.syncPlayback();
        },

        toggleCompact() {
            this.compact = !this.compact;
            try { localStorage.setItem('pgaals-compact-companion', String(this.compact)); } catch {}
            this.syncPlayback();
        },

        destroy() {
            this.stop();
            observer?.disconnect();
            motionPreference?.removeEventListener('change', motionChanged);
            window.removeEventListener('pgaals:preferences', motionChanged);
            document.removeEventListener('visibilitychange', visibilityChanged);
        },
    };
}
