export function initRolePermissions() {
    const form = document.getElementById('role-permission-form');
    if (!form || form.dataset.initialized === 'true') {
        return;
    }
    form.dataset.initialized = 'true';
    const searchInput = document.getElementById('permission-search');
    const selectAllButton = document.getElementById('select-all-permissions');
    const activeCount = document.getElementById('active-permission-count');
    const getCheckboxes = (section) =>
        Array.from(section.querySelectorAll('.permission-checkbox'));
    const getAllCheckboxes = () =>
        Array.from(form.querySelectorAll('.permission-checkbox'));
    // Memperbarui ringkasan jumlah izin pada setiap modul.
    function updateModule(section) {
        const checkboxes = getCheckboxes(section);
        const checkedCount = checkboxes.filter(
            (input) => input.checked
        ).length;
        const moduleSelect = section.querySelector('.module-select');
        const summary = section.querySelector('.module-summary');
        if (moduleSelect) {
            moduleSelect.checked =
                checkboxes.length > 0 &&
                checkedCount === checkboxes.length;
            moduleSelect.indeterminate =
                checkedCount > 0 &&
                checkedCount < checkboxes.length;
        }
        if (summary) {
            summary.textContent =
                `${checkedCount} dari ${checkboxes.length} izin aktif`;
        }
    }
    // Memperbarui seluruh ringkasan dan tombol pilih semua.
    function updateAll() {
        form.querySelectorAll('.permission-module').forEach(updateModule);
        const checkboxes = getAllCheckboxes();
        const checkedCount = checkboxes.filter(
            (input) => input.checked
        ).length;
        if (activeCount) {
            activeCount.textContent = checkedCount;
        }
        const allSelected =
            checkboxes.length > 0 &&
            checkedCount === checkboxes.length;
        if (selectAllButton) {
            selectAllButton.innerHTML = allSelected
                ? '<i class="fa-solid fa-xmark mr-2"></i>Batalkan Semua'
                : '<i class="fa-solid fa-check-double mr-2"></i>Pilih Semua';
        }
    }
    // Memilih atau membatalkan seluruh izin pada satu modul.
    form.querySelectorAll('.module-select').forEach((moduleSelect) => {
        moduleSelect.addEventListener('change', function () {
            const section = this.closest('.permission-module');
            if (!section) {
                return;
            }
            getCheckboxes(section).forEach((input) => {
                input.checked = this.checked;
            });
            updateAll();
        });
    });
    // Memperbarui ringkasan saat izin dipilih atau dibatalkan.
    form.querySelectorAll('.permission-checkbox').forEach((input) => {
        input.addEventListener('change', updateAll);
    });
    // Memilih atau membatalkan seluruh izin yang dapat diatur.
    selectAllButton?.addEventListener('click', function () {
        const checkboxes = getAllCheckboxes();
        const shouldSelectAll = !(
            checkboxes.length > 0 &&
            checkboxes.every((input) => input.checked)
        );
        checkboxes.forEach((input) => {
            input.checked = shouldSelectAll;
        });
        updateAll();
    });
    // Mencari modul, nama izin, kode, dan deskripsi.
    searchInput?.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        form.querySelectorAll('.permission-module').forEach((section) => {
            const moduleName = section.dataset.module ?? '';
            let visibleItems = 0;
            section.querySelectorAll('.permission-item').forEach((item) => {
                const searchText = item.dataset.search ?? '';
                const matches =
                    moduleName.includes(query) ||
                    searchText.includes(query);
                item.classList.toggle('hidden', !matches);
                if (matches) {
                    visibleItems++;
                }
            });
            section.classList.toggle('hidden', visibleItems === 0);
        });
    });
    // Mencegah formulir dikirim berulang kali.
    form.addEventListener('submit', function () {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Menyimpan...';
            submitButton.classList.add('opacity-70', 'cursor-wait');
        }
    });
    updateAll();
}
