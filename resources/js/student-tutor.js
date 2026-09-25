export default function studentTutor(config) {
    return {
        ...config,
        draft: '', busy: false, error: '', notice: '', confirmDelete: false,
        requestId: null, sentDraft: null, sentContext: null, textSize: 17,
        init() {
            this.$nextTick(() => this.scrollMessages());
        },
        scrollMessages() {
            const panel = this.$refs.messages;
            if (panel) panel.scrollTop = panel.scrollHeight;
        },
        choosePrompt(question) {
            this.draft = question;
            this.$refs.question.focus();
        },
        async send() {
            const question = this.draft.trim();
            if (this.busy || !this.available || !question || question.length > 1500) return;
            const context = JSON.stringify([this.chatId, this.submissionId, this.subject]);
            if (this.sentDraft !== question || this.sentContext !== context || !this.requestId) {
                this.requestId = this.makeId();
                this.sentDraft = question;
                this.sentContext = context;
            }
            this.busy = true;
            this.error = '';
            this.notice = '';
            try {
                const { data } = await window.axios.post(this.sendUrl, {
                    question, request_id: this.requestId, subject: this.subject,
                    chat_id: this.chatId, submission_id: this.submissionId,
                }, { timeout: 80000 });
                if (!this.turns.some(turn => turn.id === data.turn.id)) this.turns.push(data.turn);
                this.chatId = data.chat_id;
                this.title = data.title;
                this.deleteUrl = data.delete_url;
                this.helpUrl = data.help_url;
                this.chats = [{ id: data.chat_id, title: data.title, url: data.url }, ...this.chats.filter(chat => chat.id !== data.chat_id)];
                window.history.replaceState({}, '', data.url);
                this.draft = '';
                this.requestId = null;
                this.sentDraft = null;
                this.sentContext = null;
                this.$nextTick(() => this.scrollMessages());
            } catch (error) {
                this.error = this.errorMessage(error);
            } finally {
                this.busy = false;
                this.$nextTick(() => this.$refs.question?.focus());
            }
        },
        async deleteChat() {
            if (this.busy || !this.deleteUrl) return;
            this.busy = true;
            this.error = '';
            try {
                const { data } = await window.axios.delete(this.deleteUrl);
                window.location.assign(data.url);
            } catch (error) {
                this.error = this.errorMessage(error);
                this.busy = false;
            }
        },
        async askTeacher() {
            if (this.busy || !this.helpUrl) return;
            this.busy = true;
            this.error = '';
            try {
                const { data } = await window.axios.post(this.helpUrl);
                this.notice = data.message;
            } catch (error) {
                this.error = this.errorMessage(error);
            } finally {
                this.busy = false;
            }
        },
        errorMessage(error) {
            const status = error.response?.status;
            if (status === 401 || status === 419) return 'Your session has expired. Refresh this page and sign in again.';
            if (status === 403 || status === 404) return 'This conversation is no longer available to your account. Start a new chat.';
            return error.response?.data?.message || 'Unable to reach the tutor. Your question is still here. Check your connection and try again.';
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
