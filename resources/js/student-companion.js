export default function studentCompanion(messages, tutor = {}) {
    let timer;
    let observer;
    let motionPreference;
    let visibilityChanged;
    let motionChanged;

    return {
        messages,
        tutor,
        messageIndex: 0,
        displayText: messages[0],
        isSpeaking: false,
        isPaused: false,
        isVisible: false,
        reducedMotion: false,
        documentHidden: document.hidden,
        isMobile: false,
        compact: true,
        chatOpen: false,
        quickQuestion: '',
        quickSubject: tutor.subject || 'literacy',
        tutorTurns: [],
        pendingQuestion: '',
        tutorChatId: null,
        tutorChatUrl: '',
        tutorBusy: false,
        tutorError: '',
        tutorRequestId: null,
        tutorSentQuestion: null,
        tutorSentContext: null,

        get message() {
            return this.messages[this.messageIndex];
        },

        get canAnimate() {
            return this.isVisible && !this.documentHidden && !this.isPaused && !this.reducedMotion && !this.chatOpen && this.messages.length > 1;
        },

        get tutorAvailable() {
            return Boolean(this.tutor?.available && this.tutor?.sendUrl);
        },

        get showQuickTutor() {
            return this.chatOpen;
        },

        get canAskTutor() {
            const question = this.quickQuestion.trim();
            return this.tutorAvailable && !this.tutorBusy && question.length > 0 && question.length <= 1500;
        },

        get quickTutorStatus() {
            if (!this.tutorAvailable) return 'Tutor not connected yet.';
            if (this.tutorBusy) return 'Thinking...';
            return this.tutorError;
        },

        init() {
            this.isMobile = this.$el.classList.contains('campus-companion-mobile');
            try {
                const savedCompact = localStorage.getItem('pgaals-compact-companion');
                this.compact = savedCompact === null ? !this.tutorAvailable : savedCompact !== 'false';
            } catch {
                this.compact = !this.tutorAvailable;
            }
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
            if (this.messages.length <= 1) {
                this.displayText = this.message;
                return;
            }
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
                    if (this.messages.length > 1) {
                        timer = setTimeout(() => this.nextMessage(), 5500);
                    }
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
            if (!this.compact) this.$nextTick(() => this.scrollTutorChat());
        },

        openTutorPanel() {
            this.chatOpen = true;
            this.compact = false;
            this.stop();
            this.displayText = this.message;
            try { localStorage.setItem('pgaals-compact-companion', 'false'); } catch {}
            this.$nextTick(() => {
                this.scrollTutorChat();
                this.$refs.quickQuestion?.focus();
            });
        },

        closeTutorPanel() {
            this.chatOpen = false;
            this.tutorError = '';
            this.syncPlayback();
        },

        async askTutor() {
            const question = this.quickQuestion.trim();
            if (!this.canAskTutor) return;

            const context = JSON.stringify([this.tutorChatId, this.quickSubject]);
            if (this.tutorSentQuestion !== question || this.tutorSentContext !== context || !this.tutorRequestId) {
                this.tutorRequestId = this.makeId();
                this.tutorSentQuestion = question;
                this.tutorSentContext = context;
            }

            this.tutorBusy = true;
            this.tutorError = '';
            this.pendingQuestion = question;
            this.stop();
            this.$nextTick(() => this.scrollTutorChat());
            try {
                const { data } = await window.axios.post(this.tutor.sendUrl, {
                    question,
                    request_id: this.tutorRequestId,
                    subject: this.quickSubject,
                    chat_id: this.tutorChatId,
                    submission_id: null,
                }, { timeout: 80000 });
                if (data.chat_id) this.tutorChatId = data.chat_id;
                this.tutorChatUrl = data.url || this.tutor.tutorUrl || '';
                if (data.turn && !this.tutorTurns.some(turn => turn.id === data.turn.id)) {
                    this.tutorTurns.push(data.turn);
                }
                this.displayText = this.message;
                this.quickQuestion = '';
                this.tutorRequestId = null;
                this.tutorSentQuestion = null;
                this.tutorSentContext = null;
            } catch (error) {
                this.tutorError = this.errorMessage(error);
            } finally {
                this.pendingQuestion = '';
                this.tutorBusy = false;
                this.$nextTick(() => {
                    this.scrollTutorChat();
                    this.$refs.quickQuestion?.focus();
                });
            }
        },

        newTutorChat() {
            if (this.tutorBusy) return;
            this.tutorTurns = [];
            this.pendingQuestion = '';
            this.tutorChatId = null;
            this.tutorChatUrl = '';
            this.tutorError = '';
            this.tutorRequestId = null;
            this.tutorSentQuestion = null;
            this.tutorSentContext = null;
            this.displayText = this.message;
            this.$nextTick(() => this.$refs.quickQuestion?.focus());
        },

        scrollTutorChat() {
            const log = this.$refs.avatarChatLog;
            if (log) log.scrollTop = log.scrollHeight;
        },

        errorMessage(error) {
            const status = error.response?.status;
            if (status === 401 || status === 419) return 'Refresh and sign in again.';
            if (status === 403 || status === 404) return 'Start a new tutor chat here.';
            return error.response?.data?.message || 'Tutor is not reachable. Try again.';
        },

        makeId() {
            if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID();
            const bytes = globalThis.crypto.getRandomValues(new Uint8Array(16));
            bytes[6] = (bytes[6] & 15) | 64;
            bytes[8] = (bytes[8] & 63) | 128;
            const hex = Array.from(bytes, value => value.toString(16).padStart(2, '0')).join('');
            return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
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
