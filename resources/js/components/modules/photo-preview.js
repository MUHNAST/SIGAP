const DEFAULT_MESSAGE =
    "Klik ikon kamera untuk memilih foto<br>Format JPG, JPEG, PNG • Maksimal 2 MB";
const ALLOWED_TYPES = [
    "image/jpeg",
    "image/png",
];
export function initImagePreview(maxSize = 2 * 1024 * 1024) {
    document.querySelectorAll(".image-preview-input").forEach((fileInput) => {
        // Hindari event listener terpasang dua kali
        if (fileInput.dataset.initialized === "true") {
            return;
        }
        const previewSelector = fileInput.dataset.preview;
        if (!previewSelector) {
            return;
        }
        const previewImage = document.querySelector(previewSelector);
        if (!previewImage) {
            return;
        }
        const filenameSelector = fileInput.dataset.filename;
        const filenameElement = filenameSelector
            ? document.querySelector(filenameSelector)
            : null;
        const defaultImage = previewImage.src;
        let objectURL = null;
        const resetPreview = () => {
            // Hapus object URL jika ada
            if (objectURL) {
                URL.revokeObjectURL(objectURL);
                objectURL = null;
            }
            previewImage.src = defaultImage;
            if (filenameElement) {
                filenameElement.innerHTML = DEFAULT_MESSAGE;
            }
        };
        fileInput.addEventListener("change", function () {
            const file = this.files?.[0];
            // User membatalkan pemilihan file
            if (!file) {
                resetPreview();
                return;
            }
            // Validasi format
            if (!ALLOWED_TYPES.includes(file.type)) {
                alert("Format file harus JPG, JPEG, atau PNG.");
                this.value = "";
                resetPreview();
                return;
            }
            // Validasi ukuran
            if (file.size > maxSize) {
                alert("Ukuran foto maksimal 2 MB.");
                this.value = "";
                resetPreview();
                return;
            }
            // Hapus object URL sebelumnya
            if (objectURL) {
                URL.revokeObjectURL(objectURL);
            }
            // Buat preview baru
            objectURL = URL.createObjectURL(file);
            previewImage.src = objectURL;
            if (filenameElement) {
                filenameElement.textContent = file.name;
            }
        });
        fileInput.dataset.initialized = "true";
    });
}
