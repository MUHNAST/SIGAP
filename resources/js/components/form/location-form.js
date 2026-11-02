import {
    searchableSelect,
    resetSelect,
    loadOptions
} from './tomselect.js';
// ===============================
// INIT FORM
// ===============================
export function initLocationForm(container = document) {
    container
        .querySelectorAll('form')
        .forEach(form => {
            const province =
                form.querySelector('.province-select');
            const city =
                form.querySelector('.city-select');
            const district =
                form.querySelector('.district-select');
            const village =
                form.querySelector('.village-select');
            const birthPlace =
                form.querySelector('.tempat-lahir-select');
            const employee =
                form.querySelector('.pegawai-select');
            /*
             * Form lokasi harus memiliki komponen utama.
             */
            if (
                !province ||
                !city ||
                !district ||
                !village ||
                !birthPlace
            ) {
                return;
            }
            // ===============================
            // INIT TOMSELECT
            // ===============================
            searchableSelect(
                province,
                'Pilih Provinsi'
            );
            searchableSelect(
                city,
                'Pilih Kota/Kabupaten'
            );
            searchableSelect(
                district,
                'Pilih Kecamatan'
            );
            searchableSelect(
                village,
                'Pilih Kelurahan/Desa'
            );
            searchableSelect(
                birthPlace,
                'Pilih Tempat Lahir'
            );
            // ===============================
            // EMPLOYEE SELECT
            // ===============================
            if (employee) {
                searchableSelect(
                    employee,
                    'Pilih Pegawai'
                );
                const employeeId =
                    employee.dataset.selected || '';
                if (
                    employeeId &&
                    employee.tomselect
                ) {
                    employee.tomselect.setValue(
                        String(employeeId),
                        true
                    );
                }
            }
            // ===============================
            // SELECTED VALUE
            // ===============================
            const provinceId =
                province.dataset.selected || '';
            const cityId =
                city.dataset.selected || '';
            const districtId =
                district.dataset.selected || '';
            const villageId =
                village.dataset.selected || '';
            const birthPlaceId =
                birthPlace.dataset.selected || '';
            if (
                birthPlaceId &&
                birthPlace.tomselect
            ) {
                birthPlace.tomselect.setValue(
                    String(birthPlaceId),
                    true
                );
            }
            if (
                provinceId &&
                province.tomselect
            ) {
                province.tomselect.setValue(
                    String(provinceId),
                    true
                );
            }
            // ===============================
            // PROVINCE CHANGE
            // ===============================
            province.tomselect.on(
                'change',
                value => {
                    resetSelect(
                        city,
                        'Pilih Kota/Kabupaten'
                    );
                    resetSelect(
                        district,
                        'Pilih Kecamatan'
                    );
                    resetSelect(
                        village,
                        'Pilih Kelurahan/Desa'
                    );
                    if (!value) {
                        return;
                    }
                    loadOptions(
                        `/cities/${value}`,
                        city,
                        'Pilih Kota/Kabupaten'
                    );
                }
            );
            // ===============================
            // CITY CHANGE
            // ===============================
            city.tomselect.on(
                'change',
                value => {
                    resetSelect(
                        district,
                        'Pilih Kecamatan'
                    );
                    resetSelect(
                        village,
                        'Pilih Kelurahan/Desa'
                    );
                    if (!value) {
                        return;
                    }
                    loadOptions(
                        `/districts/${value}`,
                        district,
                        'Pilih Kecamatan'
                    );
                }
            );
            // ===============================
            // DISTRICT CHANGE
            // ===============================
            district.tomselect.on(
                'change',
                value => {
                    resetSelect(
                        village,
                        'Pilih Kelurahan/Desa'
                    );
                    if (!value) {
                        return;
                    }
                    loadOptions(
                        `/urban-villages/${value}`,
                        village,
                        'Pilih Kelurahan/Desa'
                    );
                }
            );
            // ===============================
            // EDIT AUTO LOAD
            // ===============================
            (async () => {
                if (!provinceId) {
                    return;
                }
                await loadOptions(
                    `/cities/${provinceId}`,
                    city,
                    'Pilih Kota/Kabupaten',
                    cityId
                );
                if (cityId) {
                    await loadOptions(
                        `/districts/${cityId}`,
                        district,
                        'Pilih Kecamatan',
                        districtId
                    );
                }
                if (districtId) {
                    await loadOptions(
                        `/urban-villages/${districtId}`,
                        village,
                        'Pilih Kelurahan/Desa',
                        villageId
                    );
                }
            })();
        });
}
