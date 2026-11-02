export function initEmployeeEducation() {
    const statusPegawai =
        document.querySelector('[name="status_pegawai"]');
    const educationSelect =
        document.getElementById('tingkat_pendidikan');
    if (!educationSelect) {
        return;
    }
    /*
    |--------------------------------------------------------------------------
    | Pendidikan dengan Tambahan Satu Tingkat Pangkat PNS
    |--------------------------------------------------------------------------
    */
    const educationWithAdditionalRank = [
        'D3',
        'D4',
        'S1',
        'S2',
        'S3',
    ];
    /*
    |--------------------------------------------------------------------------
    | Ambil Data Pendidikan
    |--------------------------------------------------------------------------
    */
    const educationOptions =
        Array.from(educationSelect.options).map(option => {
            const pangkatPnsMinimum =
                Number(option.dataset.pangkatPnsMinimum) || 0;
            const pangkatPnsMaksimum =
                Number(option.dataset.pangkatPnsMaksimum) || 0;
            const golonganPppkMinimum =
                Number(option.dataset.golonganPppkMinimum) || 0;
            const golonganPppkMaksimum =
                Number(option.dataset.golonganPppkMaksimum) || 0;
            /*
            |--------------------------------------------------------------------------
            | Penyesuaian Maksimum Pangkat PNS
            |--------------------------------------------------------------------------
            | D3 : III/c -> III/d
            | D4 : III/d -> IV/a
            | S1 : III/d -> IV/a
            | S2 : IV/a  -> IV/b
            | S3 : IV/b  -> IV/c
            |--------------------------------------------------------------------------
            */
            const adjustedPangkatPnsMaksimum =
                educationWithAdditionalRank.includes(option.value) &&
                    pangkatPnsMaksimum > 0
                    ? pangkatPnsMaksimum + 1
                    : pangkatPnsMaksimum;
            return {
                element: option,
                value: option.value,
                pangkatPnsMinimum,
                pangkatPnsMaksimum: adjustedPangkatPnsMaksimum,
                golonganPppkMinimum,
                golonganPppkMaksimum,
            };
        });
    /*
    |--------------------------------------------------------------------------
    | Perbarui Pilihan Pendidikan
    |--------------------------------------------------------------------------
    */
    function updateEducationOptions() {
        if (!statusPegawai) {
            return;
        }
        const status = statusPegawai.value;
        educationOptions.forEach(item => {
            const option = item.element;
            /*
            |--------------------------------------------------------------------------
            | Placeholder
            |--------------------------------------------------------------------------
            */
            if (!item.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }
            /*
            |--------------------------------------------------------------------------
            | Status Belum Dipilih
            |--------------------------------------------------------------------------
            */
            if (!status) {
                option.hidden = false;
                option.disabled = false;
                return;
            }
            /*
            |--------------------------------------------------------------------------
            | PNS
            |--------------------------------------------------------------------------
            */
            if (status === 'PNS') {
                const valid =
                    item.pangkatPnsMinimum > 0 &&
                    item.pangkatPnsMaksimum > 0 &&
                    item.pangkatPnsMinimum <= item.pangkatPnsMaksimum;
                option.hidden = !valid;
                option.disabled = !valid;
                return;
            }
            /*
            |--------------------------------------------------------------------------
            | PPPK
            |--------------------------------------------------------------------------
            */
            if (status === 'PPPK') {
                const valid =
                    item.golonganPppkMinimum > 0 &&
                    item.golonganPppkMaksimum > 0 &&
                    item.golonganPppkMinimum <= item.golonganPppkMaksimum;
                option.hidden = !valid;
                option.disabled = !valid;
                return;
            }
            /*
            |--------------------------------------------------------------------------
            | Status Tidak Dikenal
            |--------------------------------------------------------------------------
            */
            option.hidden = false;
            option.disabled = false;
        });
        /*
        |--------------------------------------------------------------------------
        | Periksa Pendidikan yang Sedang Dipilih
        |--------------------------------------------------------------------------
        */
        const selectedOption =
            educationSelect.options[
            educationSelect.selectedIndex
            ];
        if (selectedOption?.disabled) {
            educationSelect.value = '';
            educationSelect.dispatchEvent(
                new Event('change', {
                    bubbles: true,
                })
            );
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Status Pegawai Berubah
    |--------------------------------------------------------------------------
    */
    statusPegawai?.addEventListener(
        'change',
        updateEducationOptions
    );
    /*
    |--------------------------------------------------------------------------
    | Inisialisasi
    |--------------------------------------------------------------------------
    */
    updateEducationOptions();
}
