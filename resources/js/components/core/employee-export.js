export function initEmployeeExport() {
    const modal = document.getElementById('exportModal');
    if (!modal) return;
    const openButton = document.getElementById('openExportModal');
    const closeButton = document.getElementById('closeExportModal');
    const cancelButton = document.getElementById('cancelExport');
    const errorMessage = document.getElementById('exportColumnError');
    const selectAllButton = document.getElementById('selectAllColumns');
    const deselectAllButton = document.getElementById('deselectAllColumns');
    const exportForm = document.getElementById('exportForm');
    const selectedColumnCount = document.getElementById('selectedColumnCount');
    const totalColumnCount = document.getElementById('totalColumnCount');
    const MAX_PDF_COLUMNS = 9;
    const checkboxes = () => [
        ...modal.querySelectorAll('.export-column'),
    ];
    function updateColumnCount() {
        const allCheckboxes = checkboxes();
        const selectedCount = allCheckboxes.filter(
            (checkbox) => checkbox.checked
        ).length;

        if (selectedColumnCount) {
            selectedColumnCount.textContent = selectedCount;
        }

        if (totalColumnCount) {
            totalColumnCount.textContent = allCheckboxes.length;
        }

        updatePdfPreview();
    }
    function openModal() {
        checkboxes().forEach((checkbox) => {
            checkbox.checked = true;
        });
        if (errorMessage) {
            errorMessage.textContent =
                'Pilih minimal satu kolom untuk melanjutkan ekspor.';
            errorMessage.classList.add('hidden');
        }
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        updateColumnCount();
    }
    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    function updatePdfPreview() {
        const previewColumnCount = document.getElementById(
            'pdfPreviewColumnCount'
        );
        const previewOrientationLabel = document.getElementById(
            'pdfPreviewOrientationLabel'
        );
        const previewPaperLabel = document.getElementById(
            'pdfPreviewPaperLabel'
        );
        const previewPage = document.getElementById('pdfPreviewPage');
        const count = checkboxes().filter(
            (checkbox) => checkbox.checked
        ).length;
        if (previewColumnCount) {
            previewColumnCount.textContent = `${count} kolom`;
            previewColumnCount.classList.toggle(
                'text-red-600',
                count > MAX_PDF_COLUMNS
            );
        }
        if (previewOrientationLabel) {
            previewOrientationLabel.textContent = 'Landscape';
        }
        if (previewPaperLabel) {
            previewPaperLabel.textContent = 'A4';
        }
        if (previewPage) {
            previewPage.style.aspectRatio = '297 / 210';
            previewPage.style.width = '100%';
            previewPage.style.maxWidth = '100%';
        }
    }
    function showError(message) {
        if (!errorMessage) return;
        errorMessage.textContent = message;
        errorMessage.classList.remove('hidden');
    }
    function clearError() {
        errorMessage?.classList.add('hidden');
    }
    openButton?.addEventListener('click', openModal);
    closeButton?.addEventListener('click', closeModal);
    cancelButton?.addEventListener('click', closeModal);
    selectAllButton?.addEventListener('click', () => {
        checkboxes().forEach((checkbox) => {
            checkbox.checked = true;
        });

        clearError();
        updateColumnCount();
    });
    deselectAllButton?.addEventListener('click', () => {
        checkboxes().forEach((checkbox) => {
            checkbox.checked = false;
        });

        clearError();
        updateColumnCount();
    });
    checkboxes().forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            clearError();
            updateColumnCount();
        });
    });
    modal.querySelectorAll('.export-submit').forEach((button) => {
        button.addEventListener('click', function () {
            const selected = checkboxes().filter(
                (checkbox) => checkbox.checked
            );
            const format = this.dataset.format;
            const baseUrl = exportForm?.dataset.exportUrl;
            if (selected.length === 0) {
                showError('Pilih minimal satu kolom untuk melanjutkan ekspor.');
                return;
            }
            if (!baseUrl || !['xlsx', 'csv', 'pdf'].includes(format)) {
                showError('Konfigurasi ekspor tidak valid. Silakan muat ulang halaman.');
                return;
            }
            if (format === 'pdf' && selected.length > MAX_PDF_COLUMNS) {
                showError(
                    `Ekspor PDF maksimal ${MAX_PDF_COLUMNS} kolom. ` +
                    `Saat ini kamu memilih ${selected.length} kolom. ` +
                    'Silakan hapus beberapa pilihan.'
                );
                return;
            }
            clearError();
            const url = baseUrl.replace(/xlsx(?=($|\?))/, format);
            const params = new URLSearchParams();
            selected.forEach((checkbox) => {
                params.append('columns[]', checkbox.value);
            });
            if (format === 'pdf') {
                params.set('orientation', 'landscape');
                params.set('paper_size', 'a4');
            }
            const separator = url.includes('?') ? '&' : '?';
            // Memulai unduhan langsung; tidak membuka pratinjau PDF.
            window.location.href =
                `${url}${separator}${params.toString()}`;
            closeModal();
        });
    });
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (
            event.key === 'Escape' &&
            !modal.classList.contains('hidden')
        ) {
            closeModal();
        }
    });
    updateColumnCount();
}
