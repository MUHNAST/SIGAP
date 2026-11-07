import { beforeEach, describe, expect, it, vi } from 'vitest';
import { initKgbEditable } from '../../../resources/js/components/kgb/kgb-editable.js';

beforeEach(() => {
    document.body.innerHTML = '';
    sessionStorage.clear();
    vi.restoreAllMocks();
    vi.useRealTimers();
    vi.unstubAllGlobals();
});

function editableForm(date = '25 Mei 2026', oldDate = '01/01/2020') {
    document.body.innerHTML = `
      <form id="kgb-print-form">
        <input id="employee-id" value="17"><input id="history-id" value="9">
        <div class="editable" data-field="tanggal_surat" contenteditable>${date}</div>
        <div class="editable" data-field="tanggal_det_lama" contenteditable>${oldDate}</div>
        <div class="editable" data-field="nomor_surat_baru" contenteditable>SK-NEW</div>
        <div class="editable" data-field="nomor_surat_lama" contenteditable>SK-OLD</div>
        <div class="editable" data-field="oleh_pejabat" contenteditable>Officer</div>
        <div class="editable" data-field="masa_kerja_golongan" contenteditable>12 Tahun 6 Bulan</div>
        <div class="editable" data-field="masa_kerja_baru" contenteditable>13 Tahun 2 Bulan</div>
        <div class="editable" data-field="gaji_pokok_lama" contenteditable>Rp 1.200.000</div>
        <div class="editable" data-field="gaji_pokok_baru" contenteditable>Rp 1.300.000</div>
        <div class="editable" data-field="peraturan" contenteditable>Regulation</div>
        <input id="tanggal_sk"><input id="tanggal_sk_lama"><input id="nomor_sk">
        <input id="nomor_sk_lama"><input id="pejabat"><input id="masa_kerja_tahun_lama">
        <input id="masa_kerja_bulan_lama"><input id="masa_kerja_tahun_baru">
        <input id="masa_kerja_bulan_baru"><input id="gaji_lama"><input id="gaji_baru">
        <input id="dasar_hukum_kgb"><button type="submit">Submit</button>
      </form><button id="kgb-print-button"></button>`;
    document.querySelectorAll('[data-field]').forEach((element) => {
        Object.defineProperty(element, 'innerText', {
            configurable: true,
            get() { return this.textContent; },
            set(value) { this.textContent = value; },
        });
    });
}

describe('KGB editable form', () => {
    it('does nothing without the KGB form and validates supported date formats and calendar dates', () => {
        expect(() => initKgbEditable()).not.toThrow();
        editableForm();
        initKgbEditable();
        expect(window.validateKgbDates()).toBe(true);
        expect(document.getElementById('tanggal_sk').value).toBe('2026-05-25');
        expect(document.getElementById('tanggal_sk_lama').value).toBe('2020-01-01');
        document.querySelector('[data-field="tanggal_surat"]').textContent = '31 Februari 2026';
        const alert = vi.spyOn(window, 'alert').mockImplementation(() => {});
        expect(window.validateKgbDates()).toBe(false);
        expect(alert).toHaveBeenCalledWith(expect.stringContaining('Tanggal surat baru tidak valid.'));
        expect(document.querySelector('[data-field="tanggal_surat"]').focus).toBeTypeOf('function');
        document.querySelector('[data-field="tanggal_surat"]').textContent = '29/02/2024';
        document.querySelector('[data-field="tanggal_det_lama"]').textContent = '-';
        expect(window.validateKgbDates()).toBe(true);
        expect(document.getElementById('tanggal_sk').value).toBe('2024-02-29');
        expect(document.getElementById('tanggal_sk_lama').value).toBe('');
    });

    it('rejects missing date inputs and invalid old dates, retaining session edits by history key', () => {
        editableForm('25 may 2026', '31/04/2020');
        sessionStorage.setItem('kgb_history_9_nomor_surat_baru', 'EDITED');
        initKgbEditable();
        expect(document.querySelector('[data-field="nomor_surat_baru"]').innerHTML).toBe('EDITED');
        const alert = vi.spyOn(window, 'alert').mockImplementation(() => {});
        expect(window.validateKgbDates()).toBe(false);
        expect(alert).toHaveBeenCalledWith(expect.stringContaining('Tanggal SK lama tidak valid.'));
        document.querySelector('[data-field="tanggal_det_lama"]').remove();
        expect(window.validateKgbDates()).toBe(false);
        expect(alert).toHaveBeenCalledWith('Tanggal SK lama tidak ditemukan pada preview.');
        expect(sessionStorage.getItem('kgb_history_9_nomor_surat_baru')).toBe('EDITED');
    });

    it('maps editable values to submission fields and prevents submit on invalid date', () => {
        editableForm();
        initKgbEditable();
        expect(window.prepareKgbForm()).toBe(true);
        expect(document.getElementById('nomor_sk').value).toBe('SK-NEW');
        expect(document.getElementById('nomor_sk_lama').value).toBe('SK-OLD');
        expect(document.getElementById('pejabat').value).toBe('Officer');
        expect(document.getElementById('masa_kerja_tahun_lama').value).toBe('12');
        expect(document.getElementById('masa_kerja_bulan_lama').value).toBe('6');
        expect(document.getElementById('masa_kerja_tahun_baru').value).toBe('13');
        expect(document.getElementById('masa_kerja_bulan_baru').value).toBe('2');
        expect(document.getElementById('gaji_lama').value).toBe('1200000');
        expect(document.getElementById('gaji_baru').value).toBe('1300000');
        expect(document.getElementById('dasar_hukum_kgb').value).toBe('Regulation');
        const submit = new Event('submit', { bubbles: true, cancelable: true });
        const alert = vi.spyOn(window, 'alert').mockImplementation(() => {});
        document.querySelector('[data-field="tanggal_surat"]').textContent = 'bad';
        document.getElementById('kgb-print-form').dispatchEvent(submit);
        expect(submit.defaultPrevented).toBe(true);
        expect(alert).toHaveBeenCalled();
    });

    it('saves edits on input/blur and hides a previously confirmed print button', () => {
        editableForm();
        sessionStorage.setItem('kgb_history_9_print_confirmed', '1');
        initKgbEditable();
        const editable = document.querySelector('[data-field="nomor_surat_baru"]');
        editable.textContent = 'Changed';
        editable.dispatchEvent(new Event('input'));
        expect(sessionStorage.getItem('kgb_history_9_nomor_surat_baru')).toBe('Changed');
        expect(document.getElementById('kgb-print-button').classList.contains('hidden')).toBe(true);
    });
});

describe('KGB print confirmation', () => {
    async function loadPrintModule() {
        vi.resetModules();
        await import('../../../resources/js/components/kgb/kgb-print.js');
    }

    it('opens/closes confirmation modal and handles missing form/validators and invalid dates', async () => {
        await loadPrintModule();
        window.openKgbPrintConfirmation();
        expect(document.getElementById('print-confirm-modal')).toBeNull();
        window.closeKgbPrintConfirmation();
        const error = vi.spyOn(console, 'error').mockImplementation(() => {});
        window.confirmKgbPrint();
        expect(error).toHaveBeenCalledWith('Form KGB tidak ditemukan.');
        document.body.innerHTML = '<div id="print-confirm-modal" class="hidden"></div><form id="kgb-print-form"></form>';
        delete window.validateKgbDates;
        delete window.prepareKgbForm;
        const alert = vi.spyOn(window, 'alert').mockImplementation(() => {});
        window.confirmKgbPrint();
        expect(alert).toHaveBeenCalledWith(expect.stringContaining('Validasi tanggal belum tersedia.'));
        window.validateKgbDates = vi.fn(() => false);
        window.confirmKgbPrint();
        expect(window.validateKgbDates).toHaveBeenCalledOnce();
    });

    it('opens print window, persists confirmation only on success, and rolls back after submit failure', async () => {
        vi.useFakeTimers();
        await loadPrintModule();
        document.body.innerHTML = `
          <div id="print-confirm-modal" class="flex"></div><form id="kgb-print-form" data-index-url=""></form>
          <input id="history-id" value="5"><input id="employee-id" value="17"><button id="kgb-print-button"></button>`;
        window.validateKgbDates = vi.fn(() => true);
        window.prepareKgbForm = vi.fn(() => true);
        const printWindow = { close: vi.fn() };
        vi.stubGlobal('open', vi.fn(() => printWindow));
        const form = document.getElementById('kgb-print-form');
        form.submit = vi.fn();
        const error = vi.spyOn(console, 'error').mockImplementation(() => {});
        await loadPrintModule();
        window.confirmKgbPrint();
        expect(form.target).toBe('kgbPrintWindow');
        expect(form.submit).toHaveBeenCalledOnce();
        expect(sessionStorage.getItem('kgb_history_5_print_confirmed')).toBe('1');
        expect(document.getElementById('kgb-print-button').classList.contains('hidden')).toBe(true);
        expect(document.getElementById('print-confirm-modal').classList.contains('hidden')).toBe(true);
        await vi.advanceTimersByTimeAsync(10_000);
        expect(vi.getTimerCount()).toBe(0);

        sessionStorage.clear();
        document.getElementById('kgb-print-button').classList.remove('hidden');
        const alert = vi.spyOn(window, 'alert').mockImplementation(() => {});
        form.submit = vi.fn(() => { throw new Error('submit failed'); });
        window.confirmKgbPrint();
        expect(sessionStorage.getItem('kgb_history_5_print_confirmed')).toBeNull();
        expect(document.getElementById('kgb-print-button').classList.contains('hidden')).toBe(false);
        expect(printWindow.close).toHaveBeenCalledOnce();
        expect(error).toHaveBeenCalledWith('Gagal mengirim form cetak KGB:', expect.any(Error));
        expect(alert).toHaveBeenCalledWith('Surat KGB gagal diproses. Silakan coba kembali.');
        vi.clearAllTimers();
    });

    it('restores the print button when the popup is blocked or preparation fails', async () => {
        await loadPrintModule();
        document.body.innerHTML = '<form id="kgb-print-form"></form><button id="kgb-print-button" class="hidden"></button>';
        window.validateKgbDates = vi.fn(() => true);
        window.prepareKgbForm = vi.fn(() => false);
        window.open = vi.fn();
        window.confirmKgbPrint();
        expect(window.open).not.toHaveBeenCalled();
        window.prepareKgbForm.mockReturnValue(true);
        window.open = vi.fn(() => null);
        const alert = vi.spyOn(window, 'alert').mockImplementation(() => {});
        window.confirmKgbPrint();
        expect(alert).toHaveBeenCalledWith(expect.stringContaining('Halaman cetak tidak dapat dibuka.'));
        expect(document.getElementById('kgb-print-button').classList.contains('hidden')).toBe(false);
        expect(sessionStorage.length).toBe(0);
    });
});
