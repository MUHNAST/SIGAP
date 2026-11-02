/**
 * ============================================================
 * KGB PRINT CONFIRMATION
 * ============================================================
 */
let kgbRedirectTimer = null;
let kgbRedirectAttempts = 0;
const KGB_REDIRECT_INTERVAL = 1000;
const KGB_MAX_REDIRECT_ATTEMPTS = 10;
/*
|--------------------------------------------------------------------------
| Buka Modal
|--------------------------------------------------------------------------
*/
function openKgbPrintConfirmation() {
    const modal = document.getElementById('print-confirm-modal');
    if (!modal) {
        return;
    }
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
/*
|--------------------------------------------------------------------------
| Tutup Modal
|--------------------------------------------------------------------------
*/
function closeKgbPrintConfirmation() {
    const modal = document.getElementById('print-confirm-modal');
    if (!modal) {
        return;
    }
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
/*
|--------------------------------------------------------------------------
| Redirect ke Index KGB
|--------------------------------------------------------------------------
*/
function redirectToKgbIndex() {
    const form = document.getElementById('kgb-print-form');
    if (!form) {
        return;
    }
    const indexUrl = form.dataset.indexUrl;
    if (!indexUrl) {
        console.error('URL index KGB tidak ditemukan.');
        return;
    }
    window.location.href = indexUrl;
}
/*
|--------------------------------------------------------------------------
| Retry Redirect
|--------------------------------------------------------------------------
*/
function startKgbRedirectRetry() {
    if (kgbRedirectTimer) {
        clearInterval(kgbRedirectTimer);
        kgbRedirectTimer = null;
    }
    kgbRedirectAttempts = 0;
    // Percobaan pertama.
    redirectToKgbIndex();
    // Coba kembali jika halaman preview masih aktif.
    kgbRedirectTimer = setInterval(() => {
        if (document.visibilityState === 'visible') {
            kgbRedirectAttempts++;
            redirectToKgbIndex();
        }
        if (kgbRedirectAttempts >= KGB_MAX_REDIRECT_ATTEMPTS) {
            clearInterval(kgbRedirectTimer);
            kgbRedirectTimer = null;
        }
    }, KGB_REDIRECT_INTERVAL);
}
/*
|--------------------------------------------------------------------------
| Konfirmasi Cetak
|--------------------------------------------------------------------------
*/
function confirmKgbPrint() {
    const form = document.getElementById('kgb-print-form');
    if (!form) {
        console.error('Form KGB tidak ditemukan.');
        return;
    }
    /*
    |--------------------------------------------------------------------------
    | Validasi tanggal sebelum menyimpan konfirmasi
    |--------------------------------------------------------------------------
    */
    if (typeof window.validateKgbDates !== 'function') {
        alert(
            'Validasi tanggal belum tersedia. Silakan muat ulang halaman preview.'
        );
        console.error('validateKgbDates() tidak ditemukan.');
        return;
    }
    if (!window.validateKgbDates()) {
        // Tetap di preview agar tanggal dapat diperbaiki.
        return;
    }
    /*
    |--------------------------------------------------------------------------
    | Siapkan seluruh data form
    |--------------------------------------------------------------------------
    */
    if (typeof window.prepareKgbForm !== 'function') {
        alert('Persiapan data surat tidak tersedia. Silakan muat ulang halaman.');
        console.error('prepareKgbForm() tidak ditemukan.');
        return;
    }
    const prepared = window.prepareKgbForm();
    if (prepared === false) {
        return;
    }
    /*
    |--------------------------------------------------------------------------
    | History ID
    |--------------------------------------------------------------------------
    */
    const historyId =
        document.getElementById('history-id')?.value || null;
    /*
    |--------------------------------------------------------------------------
    | Employee ID
    |--------------------------------------------------------------------------
    */
    const employeeId =
        document.getElementById('employee-id')?.value || '0';
    /*
    |--------------------------------------------------------------------------
    | Key Status Cetak
    |--------------------------------------------------------------------------
    */
    const printConfirmedKey = historyId
        ? `kgb_history_${historyId}_print_confirmed`
        : `kgb_${employeeId}_print_confirmed`;
    /*
    |--------------------------------------------------------------------------
    | Sembunyikan tombol cetak sementara
    |--------------------------------------------------------------------------
    */
    const printButton = document.getElementById('kgb-print-button');
    /*
    |--------------------------------------------------------------------------
    | Buka Tab Print
    |--------------------------------------------------------------------------
    | Dibuka langsung dari klik pengguna untuk menghindari popup blocker.
    |--------------------------------------------------------------------------
    */
    const printWindow = window.open('', 'kgbPrintWindow');
    if (!printWindow) {
        if (printButton) {
            printButton.classList.remove('hidden');
        }
        alert(
            'Halaman cetak tidak dapat dibuka. ' +
            'Silakan izinkan pop-up untuk situs ini.'
        );
        return;
    }
    /*
    |--------------------------------------------------------------------------
    | Simpan status konfirmasi setelah validasi dan popup berhasil
    |--------------------------------------------------------------------------
    */
    sessionStorage.setItem(printConfirmedKey, '1');
    if (printButton) {
        printButton.classList.add('hidden');
    }
    closeKgbPrintConfirmation();
    /*
    |--------------------------------------------------------------------------
    | Arahkan Form ke Tab Print
    |--------------------------------------------------------------------------
    */
    form.target = 'kgbPrintWindow';
    /*
    |--------------------------------------------------------------------------
    | Submit Form
    |--------------------------------------------------------------------------
    */
    try {
        form.submit();
    } catch (error) {
        sessionStorage.removeItem(printConfirmedKey);
        if (printButton) {
            printButton.classList.remove('hidden');
        }
        printWindow.close();
        console.error('Gagal mengirim form cetak KGB:', error);
        alert('Surat KGB gagal diproses. Silakan coba kembali.');
        return;
    }
    /*
    |--------------------------------------------------------------------------
    | Preview → Index
    |--------------------------------------------------------------------------
    */
    startKgbRedirectRetry();
}
/*
|--------------------------------------------------------------------------
| Pasang Fungsi ke Window
|--------------------------------------------------------------------------
| Mempertahankan pemanggilan onclick dari Blade.
|--------------------------------------------------------------------------
*/
window.openKgbPrintConfirmation = openKgbPrintConfirmation;
window.closeKgbPrintConfirmation = closeKgbPrintConfirmation;
window.confirmKgbPrint = confirmKgbPrint;
