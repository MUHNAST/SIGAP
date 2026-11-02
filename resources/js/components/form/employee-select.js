import {
    searchableSelect
} from './tomselect.js';
// ===============================
// INIT EMPLOYEE SELECT
// ===============================
function initEmployeeSelect(container = document) {
    container
        .querySelectorAll('.pegawai-select')
        .forEach(select => {
            searchableSelect(
                select,
                'Pilih Pegawai'
            );
            const selected =
                select.dataset.selected;
            if (selected) {
                select.tomselect.setValue(
                    String(selected),
                    true
                );
            }
        });
}
export {
    initEmployeeSelect
};
