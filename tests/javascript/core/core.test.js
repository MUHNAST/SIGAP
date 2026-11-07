import { beforeEach, describe, expect, it, vi } from 'vitest';

const lazyDependencies = vi.hoisted(() => ({
    dataTableLoaded: false,
    chartLoaded: false,
    DataTable: vi.fn(),
    chartInstances: [],
}));

vi.mock('simple-datatables', () => {
    lazyDependencies.dataTableLoaded = true;
    return { DataTable: lazyDependencies.DataTable };
});
vi.mock('chart.js/auto', () => {
    lazyDependencies.chartLoaded = true;
    class Chart {
        static getChart = vi.fn(() => null);

        constructor(canvas, config) {
            this.canvas = canvas;
            this.config = config;
            this.resize = vi.fn();
            this.update = vi.fn();
            lazyDependencies.chartInstances.push(this);
        }

        destroy() {
            this.destroyed = true;
        }
    }

    return { default: Chart };
});

import { initActivityFilter } from '../../../resources/js/components/core/activity-filter.js';
import { initAutoFocus } from '../../../resources/js/components/core/auto-focus.js';
import { initDocumentUploads } from '../../../resources/js/components/core/document-upload.js';
import { initEmployeeExport } from '../../../resources/js/components/core/employee-export.js';
import { initModal } from '../../../resources/js/components/core/modal.js';
import { initSidebar } from '../../../resources/js/components/core/sidebar.js';
import { initDataTablesAuto } from '../../../resources/js/components/core/datatable.js';
import { createDoughnutChart, createHorizontalBarChart, initChart, resizeChart } from '../../../resources/js/components/core/chart.js';
import { closeAllDropdown, initDropdown } from '../../../resources/js/components/core/dropdown.js';

const click = (element) => element.dispatchEvent(new MouseEvent('click', { bubbles: true }));

beforeEach(() => {
    vi.unstubAllGlobals();
    document.body.innerHTML = '';
    vi.clearAllMocks();
    lazyDependencies.chartInstances = [];
    window.innerWidth = 1280;
});

describe('core filters and focus', () => {
    it('clears the opposite activity filter only when a non-empty criterion is entered', () => {
        document.body.innerHTML = '<input id="search"><select id="aktivitas"><option value="">All</option><option value="login">Login</option></select>';
        const search = document.getElementById('search');
        const activity = document.getElementById('aktivitas');
        initActivityFilter();
        search.value = '  name ';
        search.dispatchEvent(new Event('input'));
        expect(activity.value).toBe('');
        activity.value = 'login';
        activity.dispatchEvent(new Event('change'));
        expect(search.value).toBe('');
        search.value = 'keep';
        search.dispatchEvent(new Event('input'));
        activity.value = 'login';
        search.value = ' ';
        search.dispatchEvent(new Event('input'));
        expect(activity.value).toBe('login');
        activity.value = '';
        activity.dispatchEvent(new Event('change'));
        expect(search.value).toBe(' ');
    });

    it('does nothing when either activity filter is absent and focuses the first eligible field', () => {
        document.body.innerHTML = '<input type="hidden"><input type="checkbox"><input type="text" disabled><textarea id="target"></textarea>';
        initActivityFilter();
        const focus = vi.spyOn(document.getElementById('target'), 'focus');
        initAutoFocus();
        expect(focus).toHaveBeenCalledOnce();
        document.body.innerHTML = '<input disabled>';
        expect(() => initAutoFocus()).not.toThrow();
    });
});

describe('document uploads', () => {
    const uploadMarkup = (existing = 'false') => `
        <div class="rounded-2xl">
            <input class="document-upload-input" data-existing="${existing}" type="file">
            <span id="namaFile_test"></span><span id="statusDokumen_test"></span>
        </div>`;
    const changeFile = (input, file) => {
        Object.defineProperty(input, 'files', { configurable: true, value: file ? [file] : [] });
        input.dispatchEvent(new Event('change'));
    };

    it('sets filename/status, rejects extension/size/empty files, and resets cancelled selections', () => {
        document.body.innerHTML = uploadMarkup('true');
        const input = document.querySelector('input');
        const alert = vi.spyOn(window, 'alert').mockImplementation(() => {});
        initDocumentUploads();
        changeFile(input, new File(['ok'], 'form.PDF', { type: 'application/pdf' }));
        expect(document.getElementById('namaFile_test').textContent).toBe('form.PDF');
        expect(document.getElementById('statusDokumen_test').textContent).toBe('Dokumen sudah dipilih.');
        changeFile(input, new File(['x'], 'form.exe'));
        expect(alert).toHaveBeenLastCalledWith('Format dokumen harus PDF, JPG, JPEG, atau PNG.');
        expect(input.value).toBe('');
        expect(document.getElementById('statusDokumen_test').textContent).toBe('Dokumen telah diunggah.');
        Object.defineProperty(input, 'files', { configurable: true, value: [{ name: 'large.png', size: 5 * 1024 * 1024 + 1 }] });
        input.dispatchEvent(new Event('change'));
        expect(alert).toHaveBeenLastCalledWith('Ukuran dokumen maksimal 5 MB.');
        changeFile(input, new File([], 'empty.pdf'));
        expect(alert).toHaveBeenLastCalledWith('File kosong tidak dapat diunggah.');
        changeFile(input, null);
        expect(document.getElementById('namaFile_test').textContent).toBe('Belum ada file terpilih.');
        expect(document.getElementById('statusDokumen_test').textContent).toBe('Dokumen telah diunggah.');
        initDocumentUploads();
        expect(input.dataset.initialized).toBe('true');
    });

    it('reports the empty state when no existing upload is present and tolerates missing card elements', () => {
        document.body.innerHTML = uploadMarkup();
        const input = document.querySelector('input');
        initDocumentUploads();
        changeFile(input, null);
        expect(document.getElementById('statusDokumen_test').textContent).toBe('Dokumen ini belum diunggah.');
        document.body.innerHTML = '<input class="document-upload-input">';
        expect(() => initDocumentUploads()).not.toThrow();
    });
});

describe('data tables and charts', () => {
    it('does not load table or chart dependencies when their elements are absent', async () => {
        await initDataTablesAuto();
        await initChart();
        expect(lazyDependencies.dataTableLoaded).toBe(false);
        expect(lazyDependencies.chartLoaded).toBe(false);
    });

    it('initializes tables once, parses options, and styles generated controls', async () => {
        document.body.innerHTML = `
            <table class="js-datatable" data-hide="0,x,2" data-perpage="25"></table>
            <table class="js-datatable" data-datatable-initialized="true"></table>
            <input class="datatable-input"><select class="datatable-selector"></select>`;
        await initDataTablesAuto();
        expect(lazyDependencies.dataTableLoaded).toBe(true);
        expect(lazyDependencies.DataTable).toHaveBeenCalledOnce();
        expect(lazyDependencies.DataTable.mock.calls[0][1].perPage).toBe(25);
        expect(lazyDependencies.DataTable.mock.calls[0][1].columns).toEqual([{ select: 0, sortable: false }, { select: 2, sortable: false }]);
        expect(document.querySelector('.js-datatable').dataset.datatableInitialized).toBe('true');
        expect(document.querySelector('.datatable-input').classList.contains('md:w-80')).toBe(true);
        await initDataTablesAuto();
        expect(lazyDependencies.DataTable).toHaveBeenCalledOnce();
    });

    it('initializes dynamically added tables and uses defaults for horizontal and doughnut charts', async () => {
        await initDataTablesAuto();
        await expect(createHorizontalBarChart('missing', [])).resolves.toBeNull();
        await expect(createDoughnutChart('missing', 'none')).resolves.toBeNull();
        document.body.innerHTML = '<canvas id="bar"></canvas><canvas id="pie"></canvas><div id="legend"></div>';
        const bar = await createHorizontalBarChart('bar', [{ label: 'A', value: '2' }, { label: 'B', value: 'bad' }]);
        expect(bar.config.data.datasets[0].data).toEqual([2, 0]);
        expect(bar.config.options.indexAxis).toBe('y');
        expect(bar.config.options.plugins.tooltip.callbacks.label({ raw: 2 })).toBe('2 Pegawai');
        const pie = await createDoughnutChart('pie', 'legend', [{ label: 'A', value: '2' }, { label: 'B', value: 'bad' }], []);
        expect(pie.config.data.datasets[0].data).toEqual([2, 0]);
        const tooltip = pie.config.options.plugins.tooltip.callbacks.label({ label: 'A', raw: 2 });
        expect(tooltip).toBe('A: 2 Pegawai (100.0%)');
        const context = {
            save: vi.fn(), restore: vi.fn(), fillText: vi.fn(),
        };
        pie.config.plugins[0].beforeDraw({ width: 100, height: 100, ctx: context });
        expect(context.fillText).toHaveBeenCalledWith(2, 50, 38);
        expect(document.getElementById('legend').textContent).toContain('(100.0%)');
        const objectChart = await createDoughnutChart('pie', 'legend', { A: 0, B: 'bad' }, []);
        expect(objectChart.config.data.labels).toEqual(['A', 'B']);
        expect(objectChart.config.plugins[0].beforeDraw).toBeTypeOf('function');
    });

    it('initializes only charts with matching data once and resizes instantiated charts', async () => {
        const canvases = [
            'chartJenisPegawai', 'ChartJenisKelamin', 'pegawaiChart', 'chartStatus',
            'chartMasaKerja', 'chartUsia', 'chartRiwayat', 'chartPangkat',
            'chartPangkatPppk', 'chartPendidikan',
        ];
        document.body.innerHTML = `${canvases.map(id => `<canvas id="${id}"></canvas>`).join('')}
            <div id="legendJenisPegawai"></div><div id="legendGender"></div><div id="legendReminder"></div>
            <div id="legendStatus"></div><div id="legendMasaKerja"></div><div id="legendUsia"></div><div id="legendRiwayat"></div>`;
        window.chartPangkat = [{ label: 'I/a', value: 3 }];
        window.chartGolonganPppk = [{ label: 'I', value: 2 }];
        window.chartJenisPegawai = [{ label: 'PNS', value: 1 }];
        window.ChartJenisKelamin = { Pria: 1 };
        window.chartReminder = { total_pangkat: 1, total_kgb: 2, total_pensiun: 3 };
        window.chartStatus = { Aktif: 1 };
        window.chartMasaKerja = { Senior: 1 };
        window.chartUsia = { Produktif: 1 };
        window.chartRiwayat = { total_kgb: 1, total_pangkat: 2, total_pensiun: 3 };
        window.chartPendidikan = [{ label: 'S1', value: 2 }];
        await initChart();
        await initChart();
        const initialized = lazyDependencies.chartInstances.slice();
        expect(initialized).toHaveLength(10);
        resizeChart();
        initialized.forEach(chart => {
            expect(chart.resize).toHaveBeenCalledOnce();
            expect(chart.update).toHaveBeenCalledWith('none');
        });
        delete window.chartPangkat;
        delete window.chartGolonganPppk;
        delete window.chartJenisPegawai;
        delete window.ChartJenisKelamin;
        delete window.chartReminder;
        delete window.chartStatus;
        delete window.chartMasaKerja;
        delete window.chartUsia;
        delete window.chartRiwayat;
        delete window.chartPendidikan;
    });

    it('initializes a table added after the initial page scan without duplicating it', async () => {
        const table = document.createElement('table');
        table.className = 'js-datatable';
        document.body.append(table);
        await vi.waitFor(() => {
            expect(table.dataset.datatableInitialized).toBe('true');
        });
        await initDataTablesAuto();
        expect(lazyDependencies.DataTable).toHaveBeenCalledOnce();
    });
});

describe('modals, sidebar, and dropdowns', () => {
    it('opens/closes modal helpers and closes modal/dropdowns on Escape', () => {
        document.body.innerHTML = '<div id="dialog" class="modal hidden"></div>';
        window.openModal('dialog');
        expect(document.getElementById('dialog').classList.contains('flex')).toBe(true);
        expect(sessionStorage.getItem('activeModal')).toBe('dialog');
        initModal();
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        expect(document.getElementById('dialog').classList.contains('hidden')).toBe(true);
        expect(sessionStorage.getItem('activeModal')).toBeNull();
        window.openModal('missing');
        expect(() => closeAllDropdown()).not.toThrow();
    });

    it('sets responsive sidebar state and toggles desktop margin safely', () => {
        document.body.innerHTML = '<aside id="sidebar"></aside><main id="mainContent"></main>';
        const sidebar = document.getElementById('sidebar');
        const main = document.getElementById('mainContent');
        initSidebar();
        expect(main.style.marginLeft).toBe('18rem');
        window.toggleSidebar();
        expect(main.style.marginLeft).toBe('0px');
        window.innerWidth = 800;
        window.dispatchEvent(new Event('resize'));
        expect(sidebar.classList.contains('-translate-x-full')).toBe(true);
        expect(main.style.marginLeft).toBe('0px');
        document.body.innerHTML = '';
        expect(() => initSidebar()).not.toThrow();
        expect(() => window.toggleSidebar()).not.toThrow();
    });

    it('coordinates user, notification, and sidebar dropdowns and dismisses outside clicks', () => {
        document.body.innerHTML = `
            <button onclick="toggleUser(event)">user button</button><button onclick="toggleNotif(event)">notif button</button>
            <div id="user" class="hidden"></div><div id="notif" class="hidden"></div>
            <button data-sidebar-dropdown-toggle="reports"><i data-dropdown-chevron></i></button>
            <div id="reports" data-sidebar-dropdown class="hidden"></div>
            <div id="other" data-sidebar-dropdown></div>`;
        initDropdown();
        const userEvent = { preventDefault: vi.fn(), stopPropagation: vi.fn() };
        window.toggleUser(userEvent);
        expect(document.getElementById('user').classList.contains('hidden')).toBe(false);
        expect(userEvent.preventDefault).toHaveBeenCalledOnce();
        window.toggleNotif();
        expect(document.getElementById('user').classList.contains('hidden')).toBe(true);
        expect(document.getElementById('notif').classList.contains('hidden')).toBe(false);
        click(document.querySelector('[data-sidebar-dropdown-toggle]'));
        expect(document.getElementById('reports').classList.contains('hidden')).toBe(false);
        expect(document.querySelector('[data-sidebar-dropdown-toggle]').getAttribute('aria-expanded')).toBe('true');
        document.body.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(document.getElementById('reports').classList.contains('hidden')).toBe(true);
        expect(document.querySelector('[data-sidebar-dropdown-toggle]').getAttribute('aria-expanded')).toBe('false');
        closeAllDropdown();
        expect(document.getElementById('notif').classList.contains('hidden')).toBe(true);
    });
});

describe('employee export dialog', () => {
    function setupExport() {
        document.body.innerHTML = `
            <button id="openExportModal"></button>
            <div id="exportModal" class="hidden">
              <button type="button" id="closeExportModal"></button><button type="button" id="cancelExport"></button>
              <button type="button" id="selectAllColumns"></button><button type="button" id="deselectAllColumns"></button>
              <form id="exportForm" data-export-url="/employees/export.xlsx?scope=all">
                <input class="export-column" value="name" type="checkbox" checked>
                <input class="export-column" value="nip" type="checkbox" checked>
                <button type="button" class="export-submit" data-format="pdf"></button>
                <button type="button" class="export-submit" data-format="csv"></button>
              </form>
              <p id="exportColumnError"></p><span id="selectedColumnCount"></span><span id="totalColumnCount"></span>
              <span id="pdfPreviewColumnCount"></span><span id="pdfPreviewOrientationLabel"></span>
              <span id="pdfPreviewPaperLabel"></span><div id="pdfPreviewPage"></div>
            </div>`;
        initEmployeeExport();
        return { modal: document.getElementById('exportModal'), error: document.getElementById('exportColumnError') };
    }

    it('selects/deselects columns, enforces minimum, PDF cap, and valid formats', () => {
        const { modal, error } = setupExport();
        click(document.getElementById('deselectAllColumns'));
        click(modal.querySelector('[data-format="csv"]'));
        expect(error.textContent).toContain('Pilih minimal satu kolom');
        click(document.getElementById('selectAllColumns'));
        expect(document.getElementById('selectedColumnCount').textContent).toBe('2');
        const columns = [...modal.querySelectorAll('.export-column')];
        columns[0].checked = false;
        columns[0].dispatchEvent(new Event('change'));
        expect(document.getElementById('selectedColumnCount').textContent).toBe('1');
        for (let i = 0; i < 10; i++) {
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'export-column';
            checkbox.value = `col${i}`;
            checkbox.checked = true;
            modal.querySelector('form').appendChild(checkbox);
        }
        click(modal.querySelector('[data-format="pdf"]'));
        expect(error.textContent).toContain('maksimal 9 kolom');
        modal.querySelectorAll('.export-column').forEach((checkbox, index) => {
            checkbox.checked = index === 0;
        });
        const location = { href: '' };
        const browserWindow = window;
        vi.stubGlobal('window', new Proxy(browserWindow, {
            get(target, property) {
                return property === 'location' ? location : Reflect.get(target, property, target);
            },
        }));
        modal.querySelector('[data-format="pdf"]').dispatchEvent(new Event('click', { bubbles: true }));
        expect(location.href).toContain('/employees/export.pdf?scope=all&columns%5B%5D=name');
        expect(location.href).toContain('orientation=landscape');
        expect(modal.classList.contains('hidden')).toBe(true);
        vi.unstubAllGlobals();
        void columns;
    });

    it('validates missing export config, invalid format, and closes by controls, backdrop, or Escape', () => {
        const { modal, error } = setupExport();
        document.getElementById('exportForm').dataset.exportUrl = '';
        click(modal.querySelector('[data-format="csv"]'));
        expect(error.textContent).toContain('Konfigurasi ekspor tidak valid');
        document.getElementById('exportForm').dataset.exportUrl = '/export.xlsx';
        modal.querySelector('[data-format="csv"]').dataset.format = 'json';
        click(modal.querySelector('[data-format="json"]'));
        expect(error.textContent).toContain('Konfigurasi ekspor tidak valid');
        click(document.getElementById('openExportModal'));
        expect(modal.classList.contains('flex')).toBe(true);
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        expect(modal.classList.contains('hidden')).toBe(true);
        click(document.getElementById('openExportModal'));
        modal.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(modal.classList.contains('hidden')).toBe(true);
        click(document.getElementById('openExportModal'));
        click(document.getElementById('closeExportModal'));
        expect(modal.classList.contains('hidden')).toBe(true);
    });
});

describe('notification polling', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        sessionStorage.clear();
        document.body.innerHTML = '<span id="notificationBadge" class="hidden"></span><audio id="notificationSound"></audio>';
        const audio = document.getElementById('notificationSound');
        audio.pause = vi.fn();
        audio.play = vi.fn().mockResolvedValue();
        window.notificationConfig = { unreadUrl: '/notifications/unread' };
        vi.stubGlobal('fetch', vi.fn());
    });

    it('updates unread badge, plays once per session and stops reminder when unread count reaches zero', async () => {
        const { initNotification, initNotificationFilter } = await import('../../../resources/js/components/core/notification.js?initial');
        document.body.insertAdjacentHTML('beforeend', '<input id="searchNotification"><select id="kategoriNotification"><option></option><option>news</option></select>');
        initNotificationFilter();
        document.getElementById('searchNotification').value = 'alert';
        document.getElementById('searchNotification').dispatchEvent(new Event('input'));
        expect(document.getElementById('kategoriNotification').selectedIndex).toBe(0);
        document.getElementById('kategoriNotification').value = 'news';
        document.getElementById('kategoriNotification').dispatchEvent(new Event('change'));
        expect(document.getElementById('searchNotification').value).toBe('');

        fetch.mockResolvedValueOnce({ ok: true, json: async () => ({ count: 2 }) });
        initNotification();
        await Promise.resolve();
        await Promise.resolve();
        await Promise.resolve();
        expect(fetch).toHaveBeenCalledWith('/notifications/unread', expect.any(Object));
        expect(document.getElementById('notificationBadge').innerText).toBe(2);
        expect(document.getElementById('notificationBadge').classList.contains('hidden')).toBe(false);
        expect(document.getElementById('notificationSound').play).toHaveBeenCalledOnce();
        expect(sessionStorage.getItem('notificationSession')).toBe('1');
        fetch.mockResolvedValueOnce({ ok: true, json: async () => ({ count: 3 }) });
        await vi.advanceTimersByTimeAsync(60_000);
        expect(document.getElementById('notificationSound').play).toHaveBeenCalledTimes(2);
        fetch.mockResolvedValueOnce({ ok: true, json: async () => ({ count: 0 }) });
        await vi.advanceTimersByTimeAsync(60_000);
        expect(document.getElementById('notificationBadge').classList.contains('hidden')).toBe(true);
        vi.clearAllTimers();
    });

    it('ignores rejected audio playback and plays reminder sound while unread notifications remain', async () => {
        const { initNotification } = await import('../../../resources/js/components/core/notification.js?reminder');
        document.getElementById('notificationSound').play = vi.fn().mockRejectedValue(new Error('autoplay blocked'));
        fetch.mockResolvedValueOnce({ ok: true, json: async () => ({ count: 1 }) });
        fetch.mockResolvedValue({ ok: true, json: async () => ({ count: 1 }) });
        initNotification();
        await Promise.resolve();
        await Promise.resolve();
        await vi.advanceTimersByTimeAsync(5 * 60 * 1000);
        expect(document.getElementById('notificationSound').play).toHaveBeenCalledTimes(2);
        vi.clearAllTimers();
    });

    it('does not request without URL and logs failed requests without breaking initialization', async () => {
        const { initNotification } = await import('../../../resources/js/components/core/notification.js?missing');
        delete window.notificationConfig;
        const log = vi.spyOn(console, 'error').mockImplementation(() => {});
        initNotification();
        await Promise.resolve();
        expect(fetch).not.toHaveBeenCalled();
        window.notificationConfig = { unreadUrl: '/notifications/unread' };
        fetch.mockResolvedValueOnce({ ok: false, status: 503 });
        initNotification();
        await vi.advanceTimersByTimeAsync(0);
        expect(log).toHaveBeenCalled();
        vi.clearAllTimers();
    });
});
