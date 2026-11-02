// ==================== WELCOME CARD ====================
export function initWelcomeCard() {
    const card =
        document.getElementById("welcomeCard");
    if (!card) return;
    setTimeout(() => {
        card.classList.add(
            "opacity-0",
            "-translate-y-3"
        );
        setTimeout(() => {
            card.classList.add("hidden");
        }, 500);
    }, 8000);
}
