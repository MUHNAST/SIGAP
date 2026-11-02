/*
|--------------------------------------------------------------------------
| Sidebar
|--------------------------------------------------------------------------
*/
export function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    if (!sidebar || !main) {
        return;
    }
    const updateSidebar = () => {
        if (window.innerWidth < 1024) {
            sidebar.classList.add('-translate-x-full');
            main.style.marginLeft = '0';
        } else {
            sidebar.classList.remove('-translate-x-full');
            main.style.marginLeft = '18rem';
        }
    };
    updateSidebar();
    window.addEventListener('resize', updateSidebar);
}
window.toggleSidebar = () => {
    const sidebar = document.getElementById('sidebar');
    const main = document.getElementById('mainContent');
    if (!sidebar || !main) {
        return;
    }
    sidebar.classList.toggle('-translate-x-full');
    if (window.innerWidth >= 1024) {
        const hidden =
            sidebar.classList.contains('-translate-x-full');
        main.style.marginLeft = hidden
            ? '0'
            : '18rem';
    }
};
