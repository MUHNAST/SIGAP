import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('chart.js/auto', () => {
    throw new Error('Chart.js chunk unavailable');
});
vi.mock('simple-datatables', () => {
    throw new Error('DataTables chunk unavailable');
});

import { initActivityFilter } from '../../../resources/js/components/core/activity-filter.js';
import { initDataTablesAuto } from '../../../resources/js/components/core/datatable.js';
import { initChart } from '../../../resources/js/components/core/chart.js';

beforeEach(() => {
    document.body.innerHTML = '';
    delete window.chartJenisPegawai;
    vi.clearAllMocks();
});

describe('lazy dependency import failures', () => {
    it('reports failed chart and table imports without preventing other initializers', async () => {
        document.body.innerHTML = `
            <canvas id="chartJenisPegawai"></canvas>
            <table class="js-datatable"></table>
            <input id="search"><select id="aktivitas"><option value="">All</option></select>`;
        window.chartJenisPegawai = [{ label: 'PNS', value: 1 }];
        const log = vi.spyOn(console, 'error').mockImplementation(() => {});

        await expect(initChart()).resolves.toBeUndefined();
        await expect(initDataTablesAuto()).resolves.toBeUndefined();

        expect(log).toHaveBeenCalledWith(
            'Gagal memuat Chart.js:',
            expect.any(Error)
        );
        expect(log).toHaveBeenCalledWith(
            'Gagal memuat Simple DataTables:',
            expect.any(Error)
        );

        initActivityFilter();
        const search = document.getElementById('search');
        const activity = document.getElementById('aktivitas');
        activity.innerHTML = '<option value="">All</option><option value="login">Login</option>';
        search.value = 'query';
        activity.value = 'login';
        activity.dispatchEvent(new Event('change'));
        expect(search.value).toBe('');
    });
});
