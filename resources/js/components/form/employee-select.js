import {
    searchableSelect
} from './tomselect.js';
// ===============================
// INIT EMPLOYEE SELECT
// ===============================
async function initEmployeeSelect(container = document) {
    await Promise.all(
        [...container.querySelectorAll('.pegawai-select')]
            .map(async select => {
                const instance = await searchableSelect(
                    select,
                    'Pilih Pegawai'
                );
                const selected =
                    select.dataset.selected;
                if (selected && instance) {
                    instance.setValue(
                        String(selected),
                        true
                    );
                }
            })
    );
}
export {
    initEmployeeSelect
};
