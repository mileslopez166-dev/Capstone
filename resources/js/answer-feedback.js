export default function answerFeedback(config) {
    return {
        ...config,
        open: false,
        busy: false,
        answer: '',
        error: '',
        async toggle() {
            if (this.busy) return;
            if (this.open && !this.error) {
                this.open = false;
                return;
            }
            this.open = true;
            if (this.answer) return;
            this.busy = true;
            this.error = '';
            try {
                const { data } = await window.axios.post(this.url, {}, { timeout: 80000 });
                if (typeof data.answer !== 'string' || !data.answer.trim()) throw new Error('Empty feedback');
                this.answer = data.answer;
            } catch (error) {
                const status = error.response?.status;
                this.error = status === 401 || status === 419
                    ? 'Your session expired. Refresh and sign in again.'
                    : status === 403 || status === 404
                        ? 'This review is not available for your account. Refresh the page.'
                        : error.response?.data?.message || 'Unable to reach the AI helper. Your result is saved. Try again or ask your teacher.';
            } finally {
                this.busy = false;
            }
        },
    };
}
