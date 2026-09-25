export default function teacherAiAssistant(config) {
    return {
        ...config,
        draft: '',
        busy: false,
        error: '',
        requestId: null,
        sentDraft: null,
        init() {
            this.$nextTick(() => this.scrollMessages());
        },
        choosePrompt(prompt) {
            this.draft = prompt;
            this.$nextTick(() => this.$refs.question?.focus());
        },
        scrollMessages() {
            const panel = this.$refs.messages;
            if (panel) panel.scrollTop = panel.scrollHeight;
        },
        async send() {
            const question = this.draft.trim();
            if (this.busy || !this.available || !question || question.length > 2000) return;
            if (this.sentDraft !== question || !this.requestId) {
                this.requestId = this.makeId();
                this.sentDraft = question;
            }
            this.busy = true;
            this.error = '';
            try {
                const { data } = await window.axios.post(this.sendUrl, {
                    question,
                    request_id: this.requestId,
                }, { timeout: 80000 });
                if (!this.turns.some(turn => turn.id === data.turn.id)) this.turns.push(data.turn);
                this.draft = '';
                this.requestId = null;
                this.sentDraft = null;
                this.$nextTick(() => this.scrollMessages());
            } catch (error) {
                this.error = this.errorMessage(error);
            } finally {
                this.busy = false;
                this.$nextTick(() => this.$refs.question?.focus());
            }
        },
        errorMessage(error) {
            const status = error.response?.status;
            if (status === 401 || status === 419) return 'Your session expired. Refresh this page and sign in again.';
            if (status === 403 || status === 404) return 'Teachers AI Assistant is only available to approved teacher accounts.';
            return error.response?.data?.message || 'Unable to reach Teachers AI Assistant. Your question is still here.';
        },
        makeId() {
            if (globalThis.crypto.randomUUID) return globalThis.crypto.randomUUID();
            const bytes = globalThis.crypto.getRandomValues(new Uint8Array(16));
            bytes[6] = (bytes[6] & 15) | 64;
            bytes[8] = (bytes[8] & 63) | 128;
            const hex = Array.from(bytes, value => value.toString(16).padStart(2, '0')).join('');
            return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
        },
    };
}
