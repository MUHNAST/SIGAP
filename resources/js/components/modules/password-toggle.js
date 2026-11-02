// ===============================
// PASSWORD TOGGLE
// ===============================
export function initPasswordToggle() {
    window.togglePassword = function (inputId, button) {
        const input = document.getElementById(inputId);
        if (!input) {
            console.error('Input password tidak ditemukan:', inputId);
            return;
        }
        const icon = button?.querySelector('i');
        if (!icon) {
            console.error('Icon password tidak ditemukan.');
            return;
        }
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !isPassword);
        icon.classList.toggle('fa-eye-slash', isPassword);
    };
}
