const MAX_SIDE = 2560;
const QUALITY = 0.85;

const resize = async (file) => {
    const bitmap = await createImageBitmap(file);
    const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(bitmap.width * scale);
    canvas.height = Math.round(bitmap.height * scale);
    canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close();

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', QUALITY));

    return new File([blob], 'photo.jpg', { type: 'image/jpeg' });
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('photoResize', (reservationViewId) => ({
        state: 'idle',
        progress: 0,
        pendingFile: null,

        async send(file) {
            if (!file) {
                return;
            }

            this.state = 'uploading';
            this.progress = 0;
            this.pendingFile = await resize(file).catch(() => file);
            this.upload();
        },

        retry() {
            if (this.pendingFile) {
                this.state = 'uploading';
                this.upload();
            }
        },

        upload() {
            this.$wire.upload(
                `uploads.${reservationViewId}`,
                this.pendingFile,
                () => {
                    this.state = 'idle';
                    this.pendingFile = null;
                },
                () => {
                    this.state = 'failed';
                },
                (event) => {
                    this.progress = event.detail.progress;
                },
            );
        },
    }));
});
