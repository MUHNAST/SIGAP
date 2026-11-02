window.openModal = id => {
    const modal = document.getElementById(id);
    if (!modal) {
        return;
    }
    sessionStorage.setItem(
        'activeModal',
        id
    );
    modal.classList.remove('hidden');
    modal.classList.add('flex');
};
window.closeAllModal = () => {
    document
        .querySelectorAll('.modal')
        .forEach(modal => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    sessionStorage.removeItem('activeModal');
};
export function initModal() {
    document.addEventListener(
        'keydown',
        event => {
            if (event.key !== 'Escape') {
                return;
            }
            window.closeAllModal?.();
            window.closeAllDropdown?.();
        }
    );
}
