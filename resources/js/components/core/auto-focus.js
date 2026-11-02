export function initAutoFocus() {
    const firstInput = document.querySelector(
        'input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([disabled]), textarea:not([disabled]), select:not([disabled])'
    );

    if (firstInput) {
        firstInput.focus();
    }
}
