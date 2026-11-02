export function initEmployeeType() {
    const statusPegawai =
        document.querySelector(
            '[name="status_pegawai"]'
        );
    const rankSelect =
        document.getElementById(
            'id_pangkat'
        );
    const kodePppk =
        document.getElementById(
            'kode_pppk'
        );
    /*
    |--------------------------------------------------------------------------
    | TMT Pangkat
    |--------------------------------------------------------------------------
    */
    const tmtPangkatLama =
        document.getElementById(
            'tmt_pangkat_lama'
        );
    const tmtPangkatBaru =
        document.getElementById(
            'tmt_pangkat_baru'
        );
    const tmtPangkatLamaIcon =
        document.getElementById(
            'tmt_pangkat_lama_icon'
        );
    const tmtPangkatLamaDisabledIcon =
        document.getElementById(
            'tmt_pangkat_lama_disabled_icon'
        );
    const tmtPangkatBaruIcon =
        document.getElementById(
            'tmt_pangkat_baru_icon'
        );
    const tmtPangkatBaruDisabledIcon =
        document.getElementById(
            'tmt_pangkat_baru_disabled_icon'
        );
    const tmtPangkatLamaDisabledText =
        document.getElementById(
            'tmt_pangkat_lama_disabled_text'
        );
    const tmtPangkatBaruDisabledText =
        document.getElementById(
            'tmt_pangkat_baru_disabled_text'
        );
    const tmtPangkatRequired =
        document.getElementById(
            'tmt_pangkat_required'
        );
    if (!statusPegawai) {
        return;
    }
    /*
    |--------------------------------------------------------------------------
    | Aktif / Nonaktif Select
    |--------------------------------------------------------------------------
    */
    function setSelectDisabled(
        select,
        disabled
    ) {
        if (!select) {
            return;
        }
        if (select.tomselect) {
            if (disabled) {
                select.tomselect.disable();
            } else {
                select.tomselect.enable();
            }
            return;
        }
        select.disabled = disabled;
    }
    /*
    |--------------------------------------------------------------------------
    | Reset TMT Pangkat
    |--------------------------------------------------------------------------
    */
    function resetTmtPangkat() {
        if (tmtPangkatLama) {
            tmtPangkatLama.value = '';
        }
        if (tmtPangkatBaru) {
            tmtPangkatBaru.value = '';
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Tampilan TMT Pangkat
    |--------------------------------------------------------------------------
    */
    function setTmtPangkatAppearance(
        disabled,
        message = 'TMT Pangkat tidak berlaku untuk PPPK.'
    ) {
        /*
        | TMT Pangkat Lama
        */
        if (tmtPangkatLama) {
            tmtPangkatLama.disabled =
                disabled;
            tmtPangkatLama.required =
                !disabled;
            if (disabled) {
                tmtPangkatLama.classList.remove(
                    'bg-slate-50',
                    'border-slate-200',
                    'focus:bg-white',
                    'focus:border-blue-500',
                    'focus:ring-4',
                    'focus:ring-blue-100'
                );
                tmtPangkatLama.classList.add(
                    'bg-slate-100',
                    'border-slate-200',
                    'text-slate-400',
                    'cursor-not-allowed'
                );
            } else {
                tmtPangkatLama.classList.remove(
                    'bg-slate-100',
                    'text-slate-400',
                    'cursor-not-allowed'
                );
                tmtPangkatLama.classList.add(
                    'bg-slate-50',
                    'border-slate-200'
                );
            }
        }
        /*
        | TMT Pangkat Berikutnya
        */
        if (tmtPangkatBaru) {
            tmtPangkatBaru.disabled =
                disabled;
            if (disabled) {
                tmtPangkatBaru.classList.remove(
                    'bg-slate-50',
                    'border-slate-200',
                    'focus:bg-white',
                    'focus:border-blue-500',
                    'focus:ring-4',
                    'focus:ring-blue-100'
                );
                tmtPangkatBaru.classList.add(
                    'bg-slate-100',
                    'border-slate-200',
                    'text-slate-400',
                    'cursor-not-allowed'
                );
            } else {
                tmtPangkatBaru.classList.remove(
                    'bg-slate-100',
                    'text-slate-400',
                    'cursor-not-allowed'
                );
                tmtPangkatBaru.classList.add(
                    'bg-slate-50',
                    'border-slate-200'
                );
            }
        }
        /*
        | Ikon TMT Lama
        */
        if (tmtPangkatLamaIcon) {
            tmtPangkatLamaIcon.classList.toggle(
                'hidden',
                disabled
            );
        }
        if (tmtPangkatLamaDisabledIcon) {
            tmtPangkatLamaDisabledIcon.classList.toggle(
                'hidden',
                !disabled
            );
        }
        /*
        | Ikon TMT Baru
        */
        if (tmtPangkatBaruIcon) {
            tmtPangkatBaruIcon.classList.toggle(
                'hidden',
                disabled
            );
        }
        if (tmtPangkatBaruDisabledIcon) {
            tmtPangkatBaruDisabledIcon.classList.toggle(
                'hidden',
                !disabled
            );
        }
        /*
        | Keterangan
        */
        if (tmtPangkatLamaDisabledText) {
            tmtPangkatLamaDisabledText.textContent = '';
            const icon =
                document.createElement('i');
            icon.className =
                'fa-solid fa-ban mr-1';
            tmtPangkatLamaDisabledText.appendChild(
                icon
            );
            tmtPangkatLamaDisabledText.appendChild(
                document.createTextNode(
                    message
                )
            );
            tmtPangkatLamaDisabledText.classList.toggle(
                'hidden',
                !disabled
            );
        }
        if (tmtPangkatBaruDisabledText) {
            tmtPangkatBaruDisabledText.textContent = '';
            const icon =
                document.createElement('i');
            icon.className =
                'fa-solid fa-ban mr-1';
            tmtPangkatBaruDisabledText.appendChild(
                icon
            );
            tmtPangkatBaruDisabledText.appendChild(
                document.createTextNode(
                    message
                )
            );
            tmtPangkatBaruDisabledText.classList.toggle(
                'hidden',
                !disabled
            );
        }
        /*
        | Tanda wajib
        */
        if (tmtPangkatRequired) {
            tmtPangkatRequired.classList.toggle(
                'hidden',
                disabled
            );
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Aktif / Nonaktif TMT Pangkat
    |--------------------------------------------------------------------------
    */
    function setTmtPangkatDisabled(
        disabled,
        reset = false,
        message = 'TMT Pangkat tidak berlaku untuk PPPK.'
    ) {
        if (reset) {
            resetTmtPangkat();
        }
        setTmtPangkatAppearance(
            disabled,
            message
        );
    }
    /*
    |--------------------------------------------------------------------------
    | Cari option berdasarkan kode
    |--------------------------------------------------------------------------
    */
    function findOptionByKode(
        select,
        kode
    ) {
        if (!select) {
            return null;
        }
        return Array.from(
            select.options
        ).find(
            option =>
                String(
                    option.dataset.kode
                ) === String(kode) ||
                String(
                    option.value
                ) === String(kode)
        );
    }
    /*
    |--------------------------------------------------------------------------
    | Set nilai select
    |--------------------------------------------------------------------------
    */
    function setSelectValue(
        select,
        value
    ) {
        if (!select) {
            return;
        }
        if (select.tomselect) {
            select.tomselect.setValue(
                String(value),
                true
            );
            return;
        }
        select.value =
            String(value);
    }
    /*
    |--------------------------------------------------------------------------
    | Set Pangkat PNS = kode 0
    |--------------------------------------------------------------------------
    */
    function setBukanPns() {
        if (!rankSelect) {
            return;
        }
        const option =
            findOptionByKode(
                rankSelect,
                '0'
            );
        if (!option) {
            return;
        }
        setSelectValue(
            rankSelect,
            option.value
        );
        setSelectDisabled(
            rankSelect,
            true
        );
    }
    /*
    |--------------------------------------------------------------------------
    | Set Golongan PPPK = kode 0
    |--------------------------------------------------------------------------
    */
    function setBukanPppk() {
        if (!kodePppk) {
            return;
        }
        const option =
            findOptionByKode(
                kodePppk,
                '0'
            );
        if (!option) {
            return;
        }
        setSelectValue(
            kodePppk,
            option.value
        );
        setSelectDisabled(
            kodePppk,
            true
        );
    }
    /*
    |--------------------------------------------------------------------------
    | Status PNS
    |--------------------------------------------------------------------------
    */
    function setPns() {
        setBukanPppk();
        /*
        | TMT Pangkat berlaku untuk PNS.
        */
        setTmtPangkatDisabled(
            false,
            false
        );
    }
    /*
    |--------------------------------------------------------------------------
    | Status PPPK
    |--------------------------------------------------------------------------
    */
    function setPppk() {
        setBukanPns();
        setSelectDisabled(
            kodePppk,
            false
        );
        /*
        | TMT Pangkat tidak berlaku.
        | Nilai juga di-reset.
        */
        setTmtPangkatDisabled(
            true,
            true,
            'TMT Pangkat tidak berlaku untuk PPPK.'
        );
    }
    /*
    |--------------------------------------------------------------------------
    | Status belum dipilih
    |--------------------------------------------------------------------------
    */
    function setEmptyStatus() {
        setSelectDisabled(
            rankSelect,
            true
        );
        setSelectDisabled(
            kodePppk,
            true
        );
        /*
        | TMT Pangkat tidak aktif.
        */
        setTmtPangkatDisabled(
            true,
            true,
            'Pilih status pegawai terlebih dahulu.'
        );
    }
    /*
    |--------------------------------------------------------------------------
    | Update Status Pegawai
    |--------------------------------------------------------------------------
    */
    function updateEmployeeType() {
        const status =
            statusPegawai.value;
        if (status === 'PNS') {
            setPns();
            return;
        }
        if (status === 'PPPK') {
            setPppk();
            return;
        }
        setEmptyStatus();
    }
    /*
    |--------------------------------------------------------------------------
    | Status berubah
    |--------------------------------------------------------------------------
    */
    statusPegawai.addEventListener(
        'change',
        updateEmployeeType
    );
    /*
    |--------------------------------------------------------------------------
    | Inisialisasi awal
    |--------------------------------------------------------------------------
    */
    updateEmployeeType();
}
