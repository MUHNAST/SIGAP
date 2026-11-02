export function initEmployeeRank() {
    const educationSelect =
        document.getElementById('tingkat_pendidikan');
    const rankSelect =
        document.getElementById('id_pangkat');
    const statusPegawai =
        document.querySelector('[name="status_pegawai"]');
    const kodePppk =
        document.getElementById('kode_pppk');
    if (!educationSelect || !rankSelect) {
        return;
    }
    const urlTemplate =
        educationSelect.dataset.ranksUrl;
    if (!urlTemplate) {
        console.error(
            'URL pangkat berdasarkan pendidikan tidak ditemukan.'
        );
        return;
    }
    /*
    |--------------------------------------------------------------------------
    | Nilai Awal dari Database
    |--------------------------------------------------------------------------
    |
    | PNS  : id_pangkat
    | PPPK : kode_pppk
    |
    | Menggunakan data-selected karena option PPPK
    | belum dimuat ketika halaman pertama kali dibuka.
    |--------------------------------------------------------------------------
    */
    const currentRankId =
        rankSelect.dataset.selected || '';
    const currentPppkCode =
        kodePppk?.dataset.selected || '';
    /*
    |--------------------------------------------------------------------------
    | Status Pegawai
    |--------------------------------------------------------------------------
    */
    function getStatus() {
        return statusPegawai?.value || '';
    }
    /*
    |--------------------------------------------------------------------------
    | Aktif / Nonaktif Select
    |--------------------------------------------------------------------------
    */
    function setDisabled(select, disabled) {
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
    | Set Nilai Select
    |--------------------------------------------------------------------------
    */
    function setValue(select, value) {
        if (
            !select ||
            value === null ||
            value === undefined ||
            value === ''
        ) {
            return;
        }
        const stringValue = String(value);
        if (select.tomselect) {
            select.tomselect.setValue(stringValue, true);
            return;
        }
        select.value = stringValue;
    }
    /*
    |--------------------------------------------------------------------------
    | Reset Pangkat PNS
    |--------------------------------------------------------------------------
    */
    function resetRank(
        placeholder = 'Pilih Pangkat/Golongan'
    ) {
        if (rankSelect.tomselect) {
            const ts = rankSelect.tomselect;
            ts.clear(true);
            ts.clearOptions();
            ts.addOption({
                value: '',
                text: placeholder,
            });
            ts.refreshOptions(false);
            ts.disable();
            return;
        }
        rankSelect.innerHTML = `
            <option value="">
                ${placeholder}
            </option>
        `;
        rankSelect.value = '';
        rankSelect.disabled = true;
    }
    /*
    |--------------------------------------------------------------------------
    | Reset Golongan PPPK
    |--------------------------------------------------------------------------
    */
    function resetPppk(
        placeholder = 'Pilih Golongan PPPK'
    ) {
        if (!kodePppk) {
            return;
        }
        if (kodePppk.tomselect) {
            const ts = kodePppk.tomselect;
            ts.clear(true);
            ts.clearOptions();
            ts.addOption({
                value: '',
                text: placeholder,
            });
            ts.refreshOptions(false);
            ts.disable();
            return;
        }
        kodePppk.innerHTML = `
            <option value="">
                ${placeholder}
            </option>
        `;
        kodePppk.value = '';
        kodePppk.disabled = true;
    }
    /*
    |--------------------------------------------------------------------------
    | Set Pangkat PNS = 0
    |--------------------------------------------------------------------------
    |
    | PPPK tidak memiliki pangkat PNS.
    | id_pangkat = 0
    | Bukan PNS
    |--------------------------------------------------------------------------
    */
    function setBukanPns() {
        if (!rankSelect) {
            return;
        }
        if (rankSelect.tomselect) {
            const ts = rankSelect.tomselect;
            ts.clear(true);
            ts.clearOptions();
            ts.addOption({
                value: '0',
                text: 'Bukan PNS',
            });
            ts.refreshOptions(false);
            ts.setValue('0', true);
            ts.disable();
            return;
        }
        rankSelect.innerHTML = `
            <option value="0">
                Bukan PNS
            </option>
        `;
        rankSelect.value = '0';
        rankSelect.disabled = true;
    }
    /*
    |--------------------------------------------------------------------------
    | Set Golongan PPPK = 0
    |--------------------------------------------------------------------------
    |
    | PNS tidak memiliki golongan PPPK.
    | kode_pppk = 0
    | Bukan PPPK
    |--------------------------------------------------------------------------
    */
    function setBukanPppk() {
        if (!kodePppk) {
            return;
        }
        if (kodePppk.tomselect) {
            const ts = kodePppk.tomselect;
            ts.clear(true);
            ts.clearOptions();
            ts.addOption({
                value: '0',
                text: 'Bukan PPPK',
            });
            ts.refreshOptions(false);
            ts.setValue('0', true);
            ts.disable();
            return;
        }
        kodePppk.innerHTML = `
            <option value="0">
                Bukan PPPK
            </option>
        `;
        kodePppk.value = '0';
        kodePppk.disabled = true;
    }
    /*
    |--------------------------------------------------------------------------
    | Tampilkan Pangkat PNS
    |--------------------------------------------------------------------------
    */
    function setRankOptions(
        ranks,
        selectedRankId = ''
    ) {
        if (rankSelect.tomselect) {
            const ts = rankSelect.tomselect;
            ts.clear(true);
            ts.clearOptions();
            ts.addOption({
                value: '',
                text: 'Pilih Pangkat/Golongan',
            });
            ranks.forEach(rank => {
                ts.addOption({
                    value: String(rank.id),
                    text: `${rank.kode} - ${rank.nama}`,
                });
            });
            ts.refreshOptions(false);
            if (
                getStatus() === 'PNS' &&
                ranks.length > 0
            ) {
                ts.enable();
                if (selectedRankId !== '') {
                    const exists = ranks.some(
                        rank =>
                            String(rank.id) ===
                            String(selectedRankId)
                    );
                    if (exists) {
                        ts.setValue(
                            String(selectedRankId),
                            true
                        );
                    }
                }
            } else {
                ts.disable();
            }
            return;
        }
        rankSelect.innerHTML = `
            <option value="">
                Pilih Pangkat/Golongan
            </option>
        `;
        ranks.forEach(rank => {
            const option =
                document.createElement('option');
            option.value = String(rank.id);
            option.dataset.kode = String(rank.kode);
            option.textContent =
                `${rank.kode} - ${rank.nama}`;
            if (
                selectedRankId !== '' &&
                String(rank.id) === String(selectedRankId)
            ) {
                option.selected = true;
            }
            rankSelect.appendChild(option);
        });
        rankSelect.disabled =
            getStatus() !== 'PNS' ||
            ranks.length === 0;
    }
    /*
    |--------------------------------------------------------------------------
    | Tampilkan Golongan PPPK
    |--------------------------------------------------------------------------
    */
    function setPppkOptions(
        pppk,
        selectedKode = ''
    ) {
        if (!kodePppk) {
            return;
        }
        if (kodePppk.tomselect) {
            const ts = kodePppk.tomselect;
            ts.clear(true);
            ts.clearOptions();
            ts.addOption({
                value: '',
                text: 'Pilih Golongan PPPK',
            });
            pppk.forEach(item => {
                ts.addOption({
                    value: String(item.kode),
                    text: `${item.kode} - ${item.nama}`,
                });
            });
            ts.refreshOptions(false);
            if (
                getStatus() === 'PPPK' &&
                pppk.length > 0
            ) {
                ts.enable();
                if (selectedKode !== '') {
                    const exists = pppk.some(
                        item =>
                            String(item.kode) ===
                            String(selectedKode)
                    );
                    if (exists) {
                        ts.setValue(
                            String(selectedKode),
                            true
                        );
                    }
                }
            } else {
                ts.disable();
            }
            return;
        }
        kodePppk.innerHTML = `
            <option value="">
                Pilih Golongan PPPK
            </option>
        `;
        pppk.forEach(item => {
            const option =
                document.createElement('option');
            option.value = String(item.kode);
            option.dataset.kode = String(item.kode);
            option.textContent =
                `${item.kode} - ${item.nama}`;
            if (
                selectedKode !== '' &&
                String(item.kode) === String(selectedKode)
            ) {
                option.selected = true;
            }
            kodePppk.appendChild(option);
        });
        kodePppk.disabled =
            getStatus() !== 'PPPK' ||
            pppk.length === 0;
    }
    /*
    |--------------------------------------------------------------------------
    | Load Data Berdasarkan Pendidikan dan Status
    |--------------------------------------------------------------------------
    */
    async function loadRanks(
        educationCode,
        selectedValue = ''
    ) {
        if (!educationCode) {
            resetRank(
                'Pilih Pendidikan Terlebih Dahulu'
            );
            resetPppk();
            return;
        }
        const status = getStatus();
        if (!status) {
            resetRank(
                'Pilih Status Pegawai Terlebih Dahulu'
            );
            resetPppk(
                'Pilih Status Pegawai Terlebih Dahulu'
            );
            return;
        }
        setDisabled(rankSelect, true);
        setDisabled(kodePppk, true);
        try {
            const url = urlTemplate.replace(
                ':kode',
                encodeURIComponent(educationCode)
            );
            const params = new URLSearchParams();
            params.set('status_pegawai', status);
            const response = await fetch(
                `${url}?${params.toString()}`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }
            );
            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}`
                );
            }
            const data = await response.json();
            /*
            |--------------------------------------------------------------------------
            | Validasi Response
            |--------------------------------------------------------------------------
            */
            if (!Array.isArray(data)) {
                throw new Error(
                    'Format data pangkat/golongan tidak valid.'
                );
            }
            /*
            |--------------------------------------------------------------------------
            | PNS
            |--------------------------------------------------------------------------
            |
            | Batas maksimum pangkat sudah ditangani
            | oleh EmployeeRepository.
            |--------------------------------------------------------------------------
            */
            if (status === 'PNS') {
                setRankOptions(
                    data,
                    selectedValue
                );
                setBukanPppk();
                return;
            }
            /*
            |--------------------------------------------------------------------------
            | PPPK
            |--------------------------------------------------------------------------
            |
            | Batas golongan mengikuti konfigurasi Seeder.
            |--------------------------------------------------------------------------
            */
            if (status === 'PPPK') {
                setBukanPns();
                setPppkOptions(
                    data,
                    selectedValue
                );
                return;
            }
            /*
            |--------------------------------------------------------------------------
            | Status Tidak Dikenal
            |--------------------------------------------------------------------------
            */
            resetRank();
            resetPppk();
        } catch (error) {
            console.error(
                'Gagal memuat pangkat/golongan:',
                error
            );
            resetRank(
                'Gagal memuat Pangkat/Golongan'
            );
            resetPppk(
                'Gagal memuat Golongan PPPK'
            );
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Pendidikan Berubah
    |--------------------------------------------------------------------------
    */
    educationSelect.addEventListener(
        'change',
        function () {
            loadRanks(
                this.value,
                ''
            );
        }
    );
    /*
    |--------------------------------------------------------------------------
    | Status Pegawai Berubah
    |--------------------------------------------------------------------------
    */
    statusPegawai?.addEventListener(
        'change',
        function () {
            loadRanks(
                educationSelect.value,
                ''
            );
        }
    );
    /*
    |--------------------------------------------------------------------------
    | Inisialisasi Awal Halaman
    |--------------------------------------------------------------------------
    */
    if (educationSelect.value) {
        const selectedValue =
            getStatus() === 'PNS'
                ? currentRankId
                : currentPppkCode;
        loadRanks(
            educationSelect.value,
            selectedValue
        );
    } else {
        resetRank(
            'Pilih Pendidikan Terlebih Dahulu'
        );
        resetPppk();
    }
}
