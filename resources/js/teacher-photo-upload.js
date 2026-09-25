export default function teacherPhotoUpload() {
    return {
        preview: null,
        selectPhoto(event) {
            if (this.preview) URL.revokeObjectURL(this.preview);
            this.preview = null;
            const file = event.target.files?.[0];
            if (file && ['image/jpeg', 'image/png', 'image/webp'].includes(file.type) && file.size <= 2 * 1024 * 1024) {
                this.preview = URL.createObjectURL(file);
            }
        },
        destroy() {
            if (this.preview) URL.revokeObjectURL(this.preview);
        },
    };
}
