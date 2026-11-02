let lastUnread = null;
let reminderInterval = null;
let notificationPollInterval = null;
/**
 * Filter pencarian notifikasi.
 */
export function initNotificationFilter() {
    const search =
        document.getElementById(
            'searchNotification'
        );
    const kategori =
        document.getElementById(
            'kategoriNotification'
        );
    if (!search || !kategori) {
        return;
    }
    kategori.addEventListener(
        'change',
        function () {
            if (this.value !== '') {
                search.value = '';
            }
        }
    );
    search.addEventListener(
        'input',
        function () {
            if (
                this.value.trim() !== ''
            ) {
                kategori.selectedIndex = 0;
            }
        }
    );
}
/**
 * Cek apakah bunyi pertama kali
 * perlu dimainkan pada tab ini.
 */
function shouldPlayOnLogin() {
    if (
        !sessionStorage.getItem(
            'notificationSession'
        )
    ) {
        sessionStorage.setItem(
            'notificationSession',
            '1'
        );
        return true;
    }
    return false;
}
/**
 * Putar suara notifikasi.
 */
function playNotificationSound() {
    const audio =
        document.getElementById(
            'notificationSound'
        );
    if (!audio) {
        return;
    }
    audio.pause();
    audio.currentTime = 0;
    audio.play().catch(() => { });
}
/**
 * Update badge notifikasi.
 */
function updateBadge(count) {
    const badge =
        document.getElementById(
            'notificationBadge'
        );
    if (!badge) {
        return;
    }
    const total =
        Number(count) || 0;
    if (total > 0) {
        badge.innerText = total;
        badge.classList.remove(
            'hidden'
        );
    } else {
        badge.classList.add(
            'hidden'
        );
    }
}
/**
 * Mulai reminder suara.
 */
function startReminder() {
    if (reminderInterval) {
        return;
    }
    reminderInterval = setInterval(
        () => {
            if (
                Number(lastUnread) > 0
            ) {
                playNotificationSound();
            }
        },
        5 * 60 * 1000
    );
}
/**
 * Hentikan reminder suara.
 */
function stopReminder() {
    if (!reminderInterval) {
        return;
    }
    clearInterval(
        reminderInterval
    );
    reminderInterval = null;
}
/**
 * Ambil jumlah notifikasi unread.
 */
async function checkNotification(
    isInitial = false
) {
    const unreadUrl =
        window.notificationConfig
            ?.unreadUrl;
    if (!unreadUrl) {
        return;
    }
    try {
        const response =
            await fetch(unreadUrl, {
                headers: {
                    Accept:
                        'application/json',
                    'X-Requested-With':
                        'XMLHttpRequest'
                }
            });
        if (!response.ok) {
            throw new Error(
                `HTTP ${response.status}`
            );
        }
        const data =
            await response.json();
        const count =
            Number(data.count) || 0;
        updateBadge(count);
        /*
         * ========================================
         * INITIAL LOAD
         * ========================================
         */
        if (isInitial) {
            if (
                shouldPlayOnLogin() &&
                count > 0
            ) {
                playNotificationSound();
            }
            lastUnread = count;
            if (count > 0) {
                startReminder();
            } else {
                stopReminder();
            }
            return;
        }
        /*
         * ========================================
         * NOTIFIKASI BARU
         * ========================================
         */
        if (
            lastUnread !== null &&
            count > lastUnread
        ) {
            playNotificationSound();
        }
        if (count > 0) {
            startReminder();
        } else {
            stopReminder();
        }
        lastUnread = count;
    } catch (error) {
        console.error(
            'Gagal memeriksa notifikasi:',
            error
        );
    }
}
/**
 * Inisialisasi notification system.
 */
export function initNotification() {
    initNotificationFilter();
    /*
     * Initial check.
     */
    checkNotification(true);
    /*
     * Hindari interval ganda.
     */
    if (notificationPollInterval) {
        return;
    }
    notificationPollInterval =
        setInterval(
            () => {
                checkNotification(false);
            },
            60 * 1000
        );
}
