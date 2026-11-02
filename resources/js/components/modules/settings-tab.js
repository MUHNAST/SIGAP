const TABS = [
    'account',
    'security',
    'notification'
];
export function showTab(
    tab = 'account'
) {
    if (!TABS.includes(tab)) {
        tab = 'account';
    }
    sessionStorage.setItem(
        'active_tab',
        tab
    );
    TABS.forEach(currentTab => {
        const tabContent =
            document.getElementById(
                `${currentTab}-tab`
            );
        const tabButton =
            document.getElementById(
                `tab-${currentTab}`
            );
        tabContent?.classList.add(
            'hidden'
        );
        if (tabButton) {
            tabButton.classList.remove(
                'border-b-2',
                'border-blue-600',
                'text-blue-600',
                'font-medium'
            );
            tabButton.classList.add(
                'text-slate-500'
            );
        }
    });
    const activeContent =
        document.getElementById(
            `${tab}-tab`
        );
    const activeButton =
        document.getElementById(
            `tab-${tab}`
        );
    activeContent?.classList.remove(
        'hidden'
    );
    if (activeButton) {
        activeButton.classList.remove(
            'text-slate-500'
        );
        activeButton.classList.add(
            'border-b-2',
            'border-blue-600',
            'text-blue-600',
            'font-medium'
        );
    }
}
window.showTab = showTab;
export function initSettingTabs() {
    const tabAccount =
        document.getElementById(
            'tab-account'
        );
    if (!tabAccount) {
        return;
    }
    const activeTab =
        window.activeTabFromServer ||
        sessionStorage.getItem(
            'active_tab'
        ) ||
        'account';
    showTab(activeTab);
}
