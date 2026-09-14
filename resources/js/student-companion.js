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

        get message() {
            return this.messages[this.messageIndex];
        },

        get canAnimate() {
            return this.isVisible && !this.documentHidden && !this.isPaused && !this.reducedMotion;
        },

        init() {
            motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
            this.reducedMotion = motionPreference.matches;
            motionChanged = (event) => {
                this.reducedMotion = event.matches;
                this.syncPlayback();
            };
            visibilityChanged = () => {
                this.documentHidden = document.hidden;
                this.syncPlayback();
            };
            motionPreference.addEventListener('change', motionChanged);
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

        destroy() {
            this.stop();
            observer?.disconnect();
            motionPreference?.removeEventListener('change', motionChanged);
            document.removeEventListener('visibilitychange', visibilityChanged);
        },
    };
}
