import { beforeEach, describe, expect, it, vi } from 'vitest';

const initializers = vi.hoisted(() => ({
    initActivityFilter: vi.fn(),
    initAutoFocus: vi.fn(),
    initChart: vi.fn(),
    resizeChart: vi.fn(),
    initDataTablesAuto: vi.fn(),
    initDropdown: vi.fn(),
    initModal: vi.fn(),
    initNotification: vi.fn(),
    initSidebar: vi.fn(),
    initEmployeeExport: vi.fn(),
    initDocumentUploads: vi.fn(),
    initEmployeeEducation: vi.fn(),
    initEmployeeRank: vi.fn(),
    initEmployeeSelect: vi.fn(),
    initEmployeeType: vi.fn(),
    initLocationForm: vi.fn(),
    initKgbEditable: vi.fn(),
    initImagePreview: vi.fn(),
    initModalValidation: vi.fn(),
    initPasswordToggle: vi.fn(),
    initSettingTabs: vi.fn(),
    initWelcomeCard: vi.fn(),
    initRolePermissions: vi.fn(),
    alpineStart: vi.fn(),
}));

vi.mock('../../../resources/js/bootstrap.js', () => ({}));
vi.mock('flowbite', () => ({}));
vi.mock('alpinejs', () => ({ default: { start: initializers.alpineStart } }));
vi.mock('jquery', () => ({ default: {} }));
vi.mock('../../../resources/js/components/core/activity-filter.js', () => ({ initActivityFilter: initializers.initActivityFilter }));
vi.mock('../../../resources/js/components/core/auto-focus.js', () => ({ initAutoFocus: initializers.initAutoFocus }));
vi.mock('../../../resources/js/components/core/chart.js', () => ({ initChart: initializers.initChart, resizeChart: initializers.resizeChart }));
vi.mock('../../../resources/js/components/core/datatable.js', () => ({ initDataTablesAuto: initializers.initDataTablesAuto }));
vi.mock('../../../resources/js/components/core/dropdown.js', () => ({ initDropdown: initializers.initDropdown }));
vi.mock('../../../resources/js/components/core/modal.js', () => ({ initModal: initializers.initModal }));
vi.mock('../../../resources/js/components/core/notification.js', () => ({ initNotification: initializers.initNotification }));
vi.mock('../../../resources/js/components/core/sidebar.js', () => ({ initSidebar: initializers.initSidebar }));
vi.mock('../../../resources/js/components/core/employee-export.js', () => ({ initEmployeeExport: initializers.initEmployeeExport }));
vi.mock('../../../resources/js/components/core/document-upload.js', () => ({ initDocumentUploads: initializers.initDocumentUploads }));
vi.mock('../../../resources/js/components/form/initEmployeeEducation.js', () => ({ initEmployeeEducation: initializers.initEmployeeEducation }));
vi.mock('../../../resources/js/components/form/employee-rank.js', () => ({ initEmployeeRank: initializers.initEmployeeRank }));
vi.mock('../../../resources/js/components/form/employee-select.js', () => ({ initEmployeeSelect: initializers.initEmployeeSelect }));
vi.mock('../../../resources/js/components/form/employee-type.js', () => ({ initEmployeeType: initializers.initEmployeeType }));
vi.mock('../../../resources/js/components/form/location-form.js', () => ({ initLocationForm: initializers.initLocationForm }));
vi.mock('../../../resources/js/components/kgb/kgb-editable.js', () => ({ initKgbEditable: initializers.initKgbEditable }));
vi.mock('../../../resources/js/components/kgb/kgb-print.js', () => ({}));
vi.mock('../../../resources/js/components/modules/photo-preview.js', () => ({ initImagePreview: initializers.initImagePreview }));
vi.mock('../../../resources/js/components/modules/modal-validation.js', () => ({ initModalValidation: initializers.initModalValidation }));
vi.mock('../../../resources/js/components/modules/password-toggle.js', () => ({ initPasswordToggle: initializers.initPasswordToggle }));
vi.mock('../../../resources/js/components/modules/settings-tab.js', () => ({ initSettingTabs: initializers.initSettingTabs }));
vi.mock('../../../resources/js/components/modules/welcome-card.js', () => ({ initWelcomeCard: initializers.initWelcomeCard }));
vi.mock('../../../resources/js/components/modules/role-permissions.js', () => ({ initRolePermissions: initializers.initRolePermissions }));

const allInitializers = [
    'initEmployeeType', 'initEmployeeEducation', 'initEmployeeRank', 'initLocationForm',
    'initEmployeeSelect', 'initActivityFilter', 'initPasswordToggle', 'initAutoFocus',
    'initChart', 'initDataTablesAuto', 'initModal', 'initDropdown', 'initNotification',
    'initSidebar', 'initEmployeeExport', 'initDocumentUploads', 'initImagePreview',
    'initSettingTabs', 'initModalValidation', 'initKgbEditable', 'initWelcomeCard',
    'initRolePermissions',
];

beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    vi.useRealTimers();
});

describe('app entry-point initialization', () => {
    it('starts Alpine once and initializes each imported component once in dependency order', async () => {
        await import('../../../resources/js/app.js');
        await import('../../../resources/js/app.js');
        expect(initializers.alpineStart).toHaveBeenCalledOnce();
        document.dispatchEvent(new Event('DOMContentLoaded'));
        await vi.waitFor(() => {
            allInitializers.forEach((name) => {
                expect(initializers[name], name).toHaveBeenCalledOnce();
            });
        });
        const order = allInitializers.map(name => initializers[name].mock.invocationCallOrder[0]);
        expect(order).toEqual([...order].sort((a, b) => a - b));
    });

    it('debounces chart resizing and only resizes once after the final window resize event', async () => {
        vi.useFakeTimers();
        await import('../../../resources/js/app.js');
        window.dispatchEvent(new Event('resize'));
        vi.advanceTimersByTime(100);
        window.dispatchEvent(new Event('resize'));
        vi.advanceTimersByTime(149);
        expect(initializers.resizeChart).not.toHaveBeenCalled();
        vi.advanceTimersByTime(1);
        expect(initializers.resizeChart).toHaveBeenCalledOnce();
    });
});
