const MAX_FILE_SIZE = 5 * 1024 * 1024;
const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
export function initDocumentUploads() {
    const inputs = document.querySelectorAll('.document-upload-input');
    inputs.forEach((input) => {
        if (input.dataset.initialized === 'true') return;
        input.dataset.initialized = 'true';
        const card = input.closest('.rounded-2xl');
        // Cari elemen di dalam kartu input yang bersangkutan.
        const filenameElement = card?.querySelector(
            '[id^="namaFile_"]'
        );
        const statusElement = card?.querySelector(
            '[id^="statusDokumen_"]'
        );
        const hasExistingFile = input.dataset.existing === 'true';
        const setStatus = (message, color) => {
            if (!statusElement) return;
            statusElement.textContent = message;
            statusElement.className = `mt-2 text-xs ${color}`;
        };
        const resetStatus = () => {
            if (filenameElement) {
                filenameElement.textContent = 'Belum ada file terpilih.';
                filenameElement.className =
                    'mt-1 break-all text-xs text-slate-500';
            }
            setStatus(
                hasExistingFile
                    ? 'Dokumen telah diunggah.'
                    : 'Dokumen ini belum diunggah.',
                hasExistingFile ? 'text-emerald-700' : 'text-slate-500'
            );
        };
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file) {
                resetStatus();
                return;
            }
            const extension = file.name.split('.').pop()?.toLowerCase();
            if (!ALLOWED_EXTENSIONS.includes(extension)) {
                alert('Format dokumen harus PDF, JPG, JPEG, atau PNG.');
                input.value = '';
                resetStatus();
                return;
            }
            if (file.size > MAX_FILE_SIZE) {
                alert('Ukuran dokumen maksimal 5 MB.');
                input.value = '';
                resetStatus();
                return;
            }
            if (file.size === 0) {
                alert('File kosong tidak dapat diunggah.');
                input.value = '';
                resetStatus();
                return;
            }
            // Perubahan langsung di browser, tanpa request ke server.
            if (filenameElement) {
                filenameElement.textContent = file.name;
                filenameElement.className =
                    'mt-1 break-all text-xs text-blue-700';
            }
            setStatus('Dokumen sudah dipilih.', 'text-blue-700');
        });
    });
}
