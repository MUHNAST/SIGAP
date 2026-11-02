/*
|--------------------------------------------------------------------------
| Dropdown Helper
|--------------------------------------------------------------------------
*/
function showDropdown(element) {
    if (!element) {
        return;
    }
    element.classList.remove(
        'hidden',
        'opacity-0',
        'scale-95'
    );
    element.classList.add(
        'opacity-100',
        'scale-100'
    );
}
function hideDropdown(element) {
    if (!element) {
        return;
    }
    element.classList.add(
        'hidden',
        'opacity-0',
        'scale-95'
    );
    element.classList.remove(
        'opacity-100',
        'scale-100'
    );
}
function toggleDropdown(element) {
    if (!element) {
        return;
    }
    if (element.classList.contains('hidden')) {
        showDropdown(element);
    } else {
        hideDropdown(element);
    }
}
/*
|--------------------------------------------------------------------------
| Expose Dropdown Helper
|--------------------------------------------------------------------------
*/
window.showDropdown = showDropdown;
window.hideDropdown = hideDropdown;
/*
|--------------------------------------------------------------------------
| Update Tombol Sidebar
|--------------------------------------------------------------------------
*/
function updateSidebarDropdownButton(
    dropdown,
    expanded
) {
    if (!dropdown) {
        return;
    }
    const button = document.querySelector(
        `[data-sidebar-dropdown-toggle="${dropdown.id}"]`
    );
    if (!button) {
        return;
    }
    button.setAttribute(
        'aria-expanded',
        expanded ? 'true' : 'false'
    );
    const chevron = button.querySelector(
        '[data-dropdown-chevron]'
    );
    if (chevron) {
        chevron.classList.toggle(
            'rotate-180',
            expanded
        );
    }
}
/*
|--------------------------------------------------------------------------
| Tutup Semua Sidebar Dropdown
|--------------------------------------------------------------------------
*/
function closeSidebarDropdowns(except = null) {
    document
        .querySelectorAll('[data-sidebar-dropdown]')
        .forEach(dropdown => {
            if (dropdown === except) {
                return;
            }
            hideDropdown(dropdown);
            updateSidebarDropdownButton(
                dropdown,
                false
            );
        });
}
/*
|--------------------------------------------------------------------------
| Tutup Semua Dropdown
|--------------------------------------------------------------------------
*/
export function closeAllDropdown() {
    hideDropdown(
        document.getElementById('user')
    );
    hideDropdown(
        document.getElementById('notif')
    );
    closeSidebarDropdowns();
}
/*
|--------------------------------------------------------------------------
| User Dropdown
|--------------------------------------------------------------------------
*/
window.toggleUser = event => {
    event?.preventDefault();
    event?.stopPropagation();
    const user = document.getElementById('user');
    const notif = document.getElementById('notif');
    if (!user) {
        return;
    }
    hideDropdown(notif);
    closeSidebarDropdowns();
    toggleDropdown(user);
};
/*
|--------------------------------------------------------------------------
| Notification Dropdown
|--------------------------------------------------------------------------
*/
window.toggleNotif = event => {
    event?.preventDefault();
    event?.stopPropagation();
    const user = document.getElementById('user');
    const notif = document.getElementById('notif');
    if (!notif) {
        return;
    }
    hideDropdown(user);
    closeSidebarDropdowns();
    toggleDropdown(notif);
};
/*
|--------------------------------------------------------------------------
| Sidebar Dropdown
|--------------------------------------------------------------------------
*/
export function initDropdown() {
    document
        .querySelectorAll('[data-sidebar-dropdown-toggle]')
        .forEach(button => {
            const targetId =
                button.dataset.sidebarDropdownToggle;
            const dropdown =
                document.getElementById(targetId);
            if (!dropdown) {
                return;
            }
            const isInitiallyOpen =
                !dropdown.classList.contains('hidden');
            button.setAttribute(
                'aria-expanded',
                isInitiallyOpen
                    ? 'true'
                    : 'false'
            );
            const chevron =
                button.querySelector(
                    '[data-dropdown-chevron]'
                );
            if (chevron) {
                chevron.classList.toggle(
                    'rotate-180',
                    isInitiallyOpen
                );
            }
            button.addEventListener(
                'click',
                event => {
                    event.preventDefault();
                    event.stopPropagation();
                    const isHidden =
                        dropdown.classList.contains(
                            'hidden'
                        );
                    hideDropdown(
                        document.getElementById('user')
                    );
                    hideDropdown(
                        document.getElementById('notif')
                    );
                    closeSidebarDropdowns(
                        dropdown
                    );
                    if (isHidden) {
                        showDropdown(dropdown);
                    } else {
                        hideDropdown(dropdown);
                    }
                    updateSidebarDropdownButton(
                        dropdown,
                        isHidden
                    );
                }
            );
        });
    /*
    |--------------------------------------------------------------------------
    | Klik di Luar Dropdown
    |--------------------------------------------------------------------------
    */
    document.addEventListener(
        'click',
        event => {
            /*
            | User
            */
            const user =
                document.getElementById('user');
            const userButton =
                document.querySelector(
                    '[onclick*="toggleUser"]'
                );
            if (
                user &&
                !user.contains(event.target) &&
                !userButton?.contains(event.target)
            ) {
                hideDropdown(user);
            }
            /*
            | Notification
            */
            const notif =
                document.getElementById('notif');
            const notifButton =
                document.querySelector(
                    '[onclick*="toggleNotif"]'
                );
            if (
                notif &&
                !notif.contains(event.target) &&
                !notifButton?.contains(event.target)
            ) {
                hideDropdown(notif);
            }
            /*
            | Sidebar
            */
            document
                .querySelectorAll(
                    '[data-sidebar-dropdown]'
                )
                .forEach(dropdown => {
                    const button =
                        document.querySelector(
                            `[data-sidebar-dropdown-toggle="${dropdown.id}"]`
                        );
                    if (
                        dropdown.contains(event.target) ||
                        button?.contains(event.target)
                    ) {
                        return;
                    }
                    hideDropdown(dropdown);
                    updateSidebarDropdownButton(
                        dropdown,
                        false
                    );
                });
        }
    );
}
