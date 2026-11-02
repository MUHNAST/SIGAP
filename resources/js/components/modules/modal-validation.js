export function initModalValidation() {
    const body = document.body;
    if (!body) {
        return;
    }
    const hasValidationErrors =
        body.dataset.validationErrors === 'true';
    if (!hasValidationErrors) {
        return;
    }
    const modalId = sessionStorage.getItem('activeModal');
    if (!modalId) {
        return;
    }
    const modal = document.getElementById(modalId);
    if (!modal) {
        return;
    }
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
