export function initActivityFilter() {
    const search = document.getElementById('search');
    const aktivitas = document.getElementById('aktivitas');
    if (!search || !aktivitas) {
        return;
    }
    search.addEventListener(
        'input',
        function () {
            if (this.value.trim() !== '') {
                aktivitas.value = '';
            }
        }
    );
    aktivitas.addEventListener(
        'change',
        function () {
            if (this.value !== '') {
                search.value = '';
            }
        }
    );
}
