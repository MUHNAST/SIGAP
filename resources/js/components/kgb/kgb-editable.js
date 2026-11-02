/**
 * ============================================================
 * KGB EDITABLE / PRINT FORM
 * ============================================================
 */
export function initKgbEditable() {
    const form = document.getElementById('kgb-print-form');
    if (!form) {
        return;
    }
    const editables = document.querySelectorAll('.editable');
    const employeeId =
        document.getElementById('employee-id')?.value || '0';
    const historyId =
        document.getElementById('history-id')?.value || null;
    /*
    |--------------------------------------------------------------------------
    | Session Key
    |--------------------------------------------------------------------------
    */
    const makeKey = (field) => {
        if (historyId) {
            return `kgb_history_${historyId}_${field}`;
        }
        return `kgb_${employeeId}_${field}`;
    };
    /*
    |--------------------------------------------------------------------------
    | Key Status Konfirmasi Cetak
    |--------------------------------------------------------------------------
    */
    const printConfirmedKey = historyId
        ? `kgb_history_${historyId}_print_confirmed`
        : `kgb_${employeeId}_print_confirmed`;
    /*
    |--------------------------------------------------------------------------
    | Simpan Semua Editable
    |--------------------------------------------------------------------------
    */
    const saveAll = () => {
        editables.forEach((el) => {
            const field = el.dataset.field;
            if (!field) {
                return;
            }
            sessionStorage.setItem(makeKey(field), el.innerHTML);
        });
    };
    /*
    |--------------------------------------------------------------------------
    | Ambil Text
    |--------------------------------------------------------------------------
    */
    const getText = (field) => {
        const el = document.querySelector(
            `[data-field="${field}"]`
        );
        return el ? el.innerText.trim() : '';
    };
    /*
    |--------------------------------------------------------------------------
    | Ambil Angka
    |--------------------------------------------------------------------------
    */
    const getNumber = (field) => {
        return getText(field).replace(/[^\d]/g, '');
    };
    /*
    |--------------------------------------------------------------------------
    | Ambil Masa Kerja
    |--------------------------------------------------------------------------
    */
    const getMasaKerja = (field) => {
        const text = getText(field);
        const tahun =
            text.match(/(\d+)\s*Tahun/i)?.[1] ??
            text.match(/^(\d+)/)?.[1] ??
            '';
        const bulan =
            text.match(/(\d+)\s*Bulan/i)?.[1] ??
            text.match(/Tahun\s+(\d+)/i)?.[1] ??
            '';
        return { tahun, bulan };
    };
    /*
    |--------------------------------------------------------------------------
    | Nama Bulan Indonesia dan Inggris
    |--------------------------------------------------------------------------
    */
    const bulanIndonesia = {
        januari: { nomor: 1, nama: 'Januari' },
        january: { nomor: 1, nama: 'Januari' },
        februari: { nomor: 2, nama: 'Februari' },
        february: { nomor: 2, nama: 'Februari' },
        maret: { nomor: 3, nama: 'Maret' },
        march: { nomor: 3, nama: 'Maret' },
        april: { nomor: 4, nama: 'April' },
        mei: { nomor: 5, nama: 'Mei' },
        may: { nomor: 5, nama: 'Mei' },
        juni: { nomor: 6, nama: 'Juni' },
        june: { nomor: 6, nama: 'Juni' },
        juli: { nomor: 7, nama: 'Juli' },
        july: { nomor: 7, nama: 'Juli' },
        agustus: { nomor: 8, nama: 'Agustus' },
        august: { nomor: 8, nama: 'Agustus' },
        september: { nomor: 9, nama: 'September' },
        oktober: { nomor: 10, nama: 'Oktober' },
        october: { nomor: 10, nama: 'Oktober' },
        november: { nomor: 11, nama: 'November' },
        desember: { nomor: 12, nama: 'Desember' },
        december: { nomor: 12, nama: 'Desember' },
    };
    /*
    |--------------------------------------------------------------------------
    | Normalisasi dan Validasi Tanggal
    |--------------------------------------------------------------------------
    | Menerima:
    | 25 Mei 2026
    | 25 may 2026
    | 25 MAY 2026
    | 25/05/2026
    | 25-05-2026
    | 25.05.2026
    |
    | Menghasilkan format tampilan: 25 Mei 2026
    | Menghasilkan format database: 2026-05-25
    |--------------------------------------------------------------------------
    */
    const normalisasiTanggal = (value) => {
        const text = String(value ?? '')
            .replace(/^Pekalongan\s*,\s*/i, '')
            .replace(/\s+/g, ' ')
            .trim();
        if (text === '-') {
            return {
                valid: true,
                kosong: true,
                display: '-',
                iso: '',
            };
        }
        let hari;
        let bulan;
        let tahun;
        let namaBulan;
        const formatNamaBulan = text.match(
            /^(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})$/i
        );
        const formatAngka = text.match(
            /^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/
        );
        if (formatNamaBulan) {
            hari = Number(formatNamaBulan[1]);
            const dataBulan =
                bulanIndonesia[formatNamaBulan[2].toLowerCase()];
            if (!dataBulan) {
                return { valid: false };
            }
            bulan = dataBulan.nomor;
            namaBulan = dataBulan.nama;
            tahun = Number(formatNamaBulan[3]);
        } else if (formatAngka) {
            hari = Number(formatAngka[1]);
            bulan = Number(formatAngka[2]);
            tahun = Number(formatAngka[3]);
            namaBulan = Object.values(bulanIndonesia)
                .find((item) => item.nomor === bulan)?.nama;
        } else {
            return { valid: false };
        }
        // Validasi tanggal kalender, termasuk tahun kabisat.
        const tanggal = new Date(Date.UTC(tahun, bulan - 1, hari));
        if (
            !namaBulan ||
            tahun < 1000 ||
            tanggal.getUTCFullYear() !== tahun ||
            tanggal.getUTCMonth() !== bulan - 1 ||
            tanggal.getUTCDate() !== hari
        ) {
            return { valid: false };
        }
        const hariTeks = String(hari).padStart(2, '0');
        const bulanTeks = String(bulan).padStart(2, '0');
        return {
            valid: true,
            kosong: false,
            display: `${hariTeks} ${namaBulan} ${tahun}`,
            iso: `${tahun}-${bulanTeks}-${hariTeks}`,
        };
    };
    /*
    |--------------------------------------------------------------------------
    | Validasi Tanggal Sebelum Cetak
    |--------------------------------------------------------------------------
    */
    const validateKgbDates = () => {
        const tanggalSurat = document.querySelector(
            '[data-field="tanggal_surat"]'
        );
        const tanggalLama = document.querySelector(
            '[data-field="tanggal_det_lama"]'
        );
        const inputTanggalBaru =
            document.getElementById('tanggal_sk');
        const inputTanggalLama =
            document.getElementById('tanggal_sk_lama');
        if (!tanggalSurat || !inputTanggalBaru || !inputTanggalLama) {
            alert('Elemen tanggal surat tidak lengkap. Silakan muat ulang preview.');
            return false;
        }
        const hasilTanggalBaru = normalisasiTanggal(
            tanggalSurat.innerText
        );
        if (!hasilTanggalBaru.valid || hasilTanggalBaru.kosong) {
            alert(
                'Tanggal surat baru tidak valid.\n' +
                'Periksa penulisan tanggal, misalnya: 25 Mei 2026.'
            );
            tanggalSurat.focus();
            return false;
        }
        if (!tanggalLama) {
            alert('Tanggal SK lama tidak ditemukan pada preview.');
            return false;
        }
        const hasilTanggalLama = normalisasiTanggal(
            tanggalLama.innerText
        );
        if (!hasilTanggalLama.valid) {
            alert(
                'Tanggal SK lama tidak valid.\n' +
                'Gunakan format seperti 25 Mei 2026 atau tanda - jika tidak tersedia.'
            );
            tanggalLama.focus();
            return false;
        }
        // Normalisasi tampilan tanggal surat baru.
        tanggalSurat.textContent =
            `Pekalongan, ${hasilTanggalBaru.display}`;
        // Normalisasi tampilan tanggal SK lama jika tersedia.
        if (!hasilTanggalLama.kosong) {
            tanggalLama.textContent = hasilTanggalLama.display;
        }
        // Format ISO untuk dikirim ke server.
        inputTanggalBaru.value = hasilTanggalBaru.iso;
        inputTanggalLama.value = hasilTanggalLama.iso;
        saveAll();
        return true;
    };
    /*
    |--------------------------------------------------------------------------
    | Siapkan Data Form KGB
    |--------------------------------------------------------------------------
    */
    const prepareKgbForm = () => {
        if (!validateKgbDates()) {
            return false;
        }
        // Simpan perubahan terakhir.
        saveAll();
        /*
        |--------------------------------------------------------------------------
        | SK BARU
        |--------------------------------------------------------------------------
        */
        const nomorSkBaru = getText('nomor_surat_baru');
        document.getElementById('nomor_sk').value = nomorSkBaru;
        /*
        |--------------------------------------------------------------------------
        | SK LAMA
        |--------------------------------------------------------------------------
        */
        document.getElementById('nomor_sk_lama').value =
            getText('nomor_surat_lama');
        /*
        |--------------------------------------------------------------------------
        | PEJABAT
        |--------------------------------------------------------------------------
        */
        document.getElementById('pejabat').value =
            getText('oleh_pejabat');
        /*
        |--------------------------------------------------------------------------
        | MASA KERJA LAMA
        |--------------------------------------------------------------------------
        */
        const mkLama = getMasaKerja('masa_kerja_golongan');
        document.getElementById('masa_kerja_tahun_lama').value =
            mkLama.tahun;
        document.getElementById('masa_kerja_bulan_lama').value =
            mkLama.bulan;
        /*
        |--------------------------------------------------------------------------
        | MASA KERJA BARU
        |--------------------------------------------------------------------------
        */
        const mkBaru = getMasaKerja('masa_kerja_baru');
        document.getElementById('masa_kerja_tahun_baru').value =
            mkBaru.tahun;
        document.getElementById('masa_kerja_bulan_baru').value =
            mkBaru.bulan;
        /*
        |--------------------------------------------------------------------------
        | GAJI LAMA
        |--------------------------------------------------------------------------
        */
        document.getElementById('gaji_lama').value =
            getNumber('gaji_pokok_lama');
        /*
        |--------------------------------------------------------------------------
        | GAJI BARU
        |--------------------------------------------------------------------------
        */
        document.getElementById('gaji_baru').value =
            getNumber('gaji_pokok_baru');
        /*
        |--------------------------------------------------------------------------
        | DASAR HUKUM
        |--------------------------------------------------------------------------
        */
        document.getElementById('dasar_hukum_kgb').value =
            getText('peraturan');
        return true;
    };
    /*
    |--------------------------------------------------------------------------
    | Load Data Session Storage
    |--------------------------------------------------------------------------
    */
    editables.forEach((el) => {
        const field = el.dataset.field;
        if (!field) {
            return;
        }
        const saved = sessionStorage.getItem(makeKey(field));
        if (saved !== null) {
            el.innerHTML = saved;
        }
        el.addEventListener('input', saveAll);
        el.addEventListener('blur', saveAll);
    });
    /*
    |--------------------------------------------------------------------------
    | Sembunyikan Tombol Jika Sudah Dikonfirmasi
    |--------------------------------------------------------------------------
    */
    const printButton = document.getElementById('kgb-print-button');
    if (
        printButton &&
        sessionStorage.getItem(printConfirmedKey) === '1'
    ) {
        printButton.classList.add('hidden');
    }
    /*
    |--------------------------------------------------------------------------
    | Submit Form
    |--------------------------------------------------------------------------
    */
    form.addEventListener('submit', (event) => {
        if (!prepareKgbForm()) {
            event.preventDefault();
        }
    });
    /*
    |--------------------------------------------------------------------------
    | Export Fungsi ke Window
    |--------------------------------------------------------------------------
    */
    window.prepareKgbForm = prepareKgbForm;
    window.validateKgbDates = validateKgbDates;
}
