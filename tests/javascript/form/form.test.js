import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    get: vi.fn(),
    tomSelectLoads: 0,
    failTomSelect: false,
}));

vi.mock('jquery', () => ({ default: { get: mocks.get } }));
vi.mock('tom-select', () => ({
    get default() {
        mocks.tomSelectLoads += 1;
        if (mocks.failTomSelect) {
            throw new Error('TomSelect chunk unavailable');
        }
        return class FakeTomSelect {
            constructor(element, options) {
                this.input = element;
                this.optionsConfig = options;
                this.options = [];
                this.value = '';
                this.enabled = true;
                this.handlers = {};
                element.tomselect = this;
                options.onInitialize?.call(this);
            }

            setValue = vi.fn((value) => { this.value = value; });
            clear = vi.fn(() => { this.value = ''; });
            clearOptions = vi.fn(() => { this.options = []; });
            addOption = vi.fn((option) => { this.options.push(option); });
            refreshOptions = vi.fn();
            disable = vi.fn(() => { this.enabled = false; });
            enable = vi.fn(() => { this.enabled = true; });
            on = vi.fn((event, callback) => { this.handlers[event] = callback; });
        };
    },
}));

import { initEmployeeEducation } from '../../../resources/js/components/form/initEmployeeEducation.js';
import { initEmployeeRank } from '../../../resources/js/components/form/employee-rank.js';
import { initEmployeeSelect } from '../../../resources/js/components/form/employee-select.js';
import { initEmployeeType } from '../../../resources/js/components/form/employee-type.js';
import { initLocationForm } from '../../../resources/js/components/form/location-form.js';
import { loadOptions, resetSelect, searchableSelect } from '../../../resources/js/components/form/tomselect.js';

beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
});

describe('TomSelect helpers', () => {
    it('does not request TomSelect when no component is present and recovers from a failed import', async () => {
        expect(await searchableSelect(null)).toBeNull();
        await initEmployeeSelect(document);
        await initLocationForm(document);
        expect(mocks.tomSelectLoads).toBe(0);

        const element = document.createElement('select');
        element.className = 'pegawai-select';
        const container = document.createElement('div');
        container.append(element);
        document.body.append(container);
        const log = vi.spyOn(console, 'error').mockImplementation(() => {});
        mocks.failTomSelect = true;
        await expect(initEmployeeSelect(container)).resolves.toBeUndefined();
        expect(element.tomselect).toBeUndefined();
        expect(log).toHaveBeenCalledWith(
            'Gagal memuat TomSelect:',
            expect.any(Error)
        );
        mocks.failTomSelect = false;
        await expect(searchableSelect(element)).resolves.toBeTruthy();
        expect(mocks.tomSelectLoads).toBe(2);
    });

    it('initializes only once, restores a selected value, and handles absent elements', async () => {
        const element = document.createElement('select');
        element.dataset.selected = '12';
        const instance = await searchableSelect(element, 'Choose');
        expect(instance.value).toBe('12');
        expect(instance.optionsConfig.placeholder).toBe('Choose');
        expect(instance.optionsConfig.render.option({ text: '<option>' }, value => `escaped:${value}`)).toContain('escaped:<option>');
        expect(await searchableSelect(element)).toBe(instance);
        await expect(searchableSelect(null)).resolves.toBeNull();
    });

    it('resets options, loads arrays, restores selection, and recovers from AJAX errors', async () => {
        const element = document.createElement('select');
        const instance = await searchableSelect(element);
        resetSelect(element, 'Choose region');
        expect(instance.clear).toHaveBeenCalledOnce();
        expect(instance.options).toEqual([{ value: '', text: 'Choose region' }]);
        expect(instance.enabled).toBe(true);

        mocks.get.mockResolvedValueOnce([{ id: 8, nama: 'Central' }]);
        await loadOptions('/regions', element, 'Choose', 8);
        expect(mocks.get).toHaveBeenCalledWith('/regions');
        expect(instance.options).toEqual([{ value: '', text: 'Choose' }, { value: '8', text: 'Central' }]);
        expect(instance.value).toBe('8');
        expect(instance.enabled).toBe(true);

        mocks.get.mockResolvedValueOnce({ data: [] });
        await loadOptions('/empty', element, 'Empty');
        expect(instance.options).toEqual([{ value: '', text: 'Empty' }]);

        const error = new Error('offline');
        mocks.get.mockRejectedValueOnce(error);
        const log = vi.spyOn(console, 'error').mockImplementation(() => {});
        await loadOptions('/failed', element, 'Retry');
        expect(log).toHaveBeenCalledWith('Gagal memuat data:', error);
        expect(instance.options).toEqual([{ value: '', text: 'Gagal memuat data' }]);
        expect(instance.enabled).toBe(true);
        await expect(loadOptions('/missing', document.createElement('select'), 'Empty')).resolves.toBeUndefined();
    });
});

describe('employee selection and location form', () => {
    it('initializes employee selectors and restores the selected employee', async () => {
        document.body.innerHTML = '<div id="form"><select class="pegawai-select" data-selected="41"></select></div>';
        const container = document.getElementById('form');
        await initEmployeeSelect(container);
        expect(container.querySelector('select').tomselect.setValue).toHaveBeenCalledWith('41', true);
    });

    it('skips incomplete location forms and wires dependent location loads and edit restoration', async () => {
        document.body.innerHTML = `
          <form id="invalid"></form>
          <form id="valid">
            <select class="province-select" data-selected="1"></select><select class="city-select" data-selected="2"></select>
            <select class="district-select" data-selected="3"></select><select class="village-select" data-selected="4"></select>
            <select class="tempat-lahir-select" data-selected="7"></select><select class="pegawai-select" data-selected="8"></select>
          </form>`;
        mocks.get.mockResolvedValue([]);
        await initLocationForm();
        await vi.waitFor(() => expect(mocks.get).toHaveBeenCalledTimes(3));
        expect(mocks.get).toHaveBeenNthCalledWith(1, '/cities/1');
        expect(document.querySelector('.province-select').tomselect.setValue).toHaveBeenCalledWith('1', true);
        document.querySelector('.province-select').tomselect.handlers.change('');
        expect(document.querySelector('.city-select').tomselect.options).toEqual([{ value: '', text: 'Pilih Kota/Kabupaten' }]);
        expect(mocks.get).toHaveBeenCalledTimes(3);
        document.querySelector('.city-select').tomselect.handlers.change('22');
        expect(mocks.get).toHaveBeenLastCalledWith('/districts/22');
        document.querySelector('.district-select').tomselect.handlers.change('33');
        expect(mocks.get).toHaveBeenLastCalledWith('/urban-villages/33');
    });
});

describe('employee education rules', () => {
    function educationOption(value, minPns, maxPns, minPppk, maxPppk) {
        const option = document.createElement('option');
        option.value = value;
        Object.assign(option.dataset, {
            pangkatPnsMinimum: String(minPns),
            pangkatPnsMaksimum: String(maxPns),
            golonganPppkMinimum: String(minPppk),
            golonganPppkMaksimum: String(maxPppk),
        });
        return option;
    }

    it('filters education for PNS and PPPK, adjusts selected rank level, and clears invalid selection', () => {
        document.body.innerHTML = `
          <select name="status_pegawai"><option value="">Select</option><option>PNS</option><option>PPPK</option><option>OTHER</option></select>
          <select id="tingkat_pendidikan"></select>`;
        const education = document.getElementById('tingkat_pendidikan');
        education.append(educationOption('', 0, 0, 0, 0), educationOption('D3', 2, 1, 0, 0), educationOption('SMA', 0, 0, 1, 5));
        const status = document.querySelector('[name="status_pegawai"]');
        initEmployeeEducation();
        expect([...education.options].every(option => !option.disabled)).toBe(true);
        status.value = 'PNS';
        status.dispatchEvent(new Event('change'));
        expect(education.options[1].disabled).toBe(false);
        expect(education.options[2].disabled).toBe(true);
        expect(education.options[1].disabled).toBe(false);
        status.value = 'PPPK';
        education.value = 'D3';
        status.dispatchEvent(new Event('change'));
        expect(education.value).toBe('');
        expect(education.options[1].disabled).toBe(true);
        expect(education.options[2].disabled).toBe(false);
        status.value = 'UNKNOWN';
        status.dispatchEvent(new Event('change'));
        expect([...education.options].every(option => !option.disabled)).toBe(true);
    });

    it('does nothing when the education select or employee status control is absent', () => {
        expect(() => initEmployeeEducation()).not.toThrow();
        document.body.innerHTML = '<select id="tingkat_pendidikan"><option value="">Select</option></select>';
        expect(() => initEmployeeEducation()).not.toThrow();
    });
});

describe('employee type state changes', () => {
    function setupType(status) {
        document.body.innerHTML = `
          <select name="status_pegawai"><option value=""></option><option>PNS</option><option>PPPK</option></select>
          <select id="id_pangkat"><option value="0" data-kode="0">Bukan PNS</option><option value="2">Rank</option></select>
          <select id="kode_pppk"><option value="0" data-kode="0">Bukan PPPK</option><option value="II">II</option></select>
          <input id="tmt_pangkat_lama" value="old"><input id="tmt_pangkat_baru" value="new">
          <i id="tmt_pangkat_lama_icon"></i><i id="tmt_pangkat_lama_disabled_icon"></i>
          <i id="tmt_pangkat_baru_icon"></i><i id="tmt_pangkat_baru_disabled_icon"></i>
          <span id="tmt_pangkat_lama_disabled_text"></span><span id="tmt_pangkat_baru_disabled_text"></span><b id="tmt_pangkat_required"></b>`;
        const select = document.querySelector('[name="status_pegawai"]');
        select.value = status;
        return select;
    }

    it('applies PNS/PPPK/empty state, TMT requirements, code-zero selections, and clears TMT for non-PNS', () => {
        const status = setupType('PNS');
        initEmployeeType();
        expect(document.getElementById('tmt_pangkat_lama').disabled).toBe(false);
        expect(document.getElementById('tmt_pangkat_lama').required).toBe(true);
        status.value = 'PPPK';
        status.dispatchEvent(new Event('change'));
        expect(document.getElementById('id_pangkat').value).toBe('0');
        expect(document.getElementById('kode_pppk').disabled).toBe(false);
        expect(document.getElementById('tmt_pangkat_lama').value).toBe('');
        expect(document.getElementById('tmt_pangkat_required').classList.contains('hidden')).toBe(true);
        status.value = '';
        document.getElementById('tmt_pangkat_lama').value = 'set';
        status.dispatchEvent(new Event('change'));
        expect(document.getElementById('kode_pppk').disabled).toBe(true);
        expect(document.getElementById('tmt_pangkat_lama').value).toBe('');
        expect(document.getElementById('tmt_pangkat_lama_disabled_text').textContent).toContain('Pilih status pegawai terlebih dahulu.');
    });
});

describe('employee rank loading', () => {
    function setupRank(status = 'PNS', education = 'S1', tomselect = false) {
        document.body.innerHTML = `
          <select id="tingkat_pendidikan" data-ranks-url="/ranks/:kode"><option value="S1">S1</option></select>
          <select name="status_pegawai"><option>PNS</option><option>PPPK</option><option>OTHER</option></select>
          <select id="id_pangkat" data-selected="2"><option value="">Pick</option></select>
          <select id="kode_pppk" data-selected="III"><option value="0">No</option></select>`;
        const educationSelect = document.getElementById('tingkat_pendidikan');
        educationSelect.value = education;
        document.querySelector('[name="status_pegawai"]').value = status;
        if (tomselect) {
            for (const select of document.querySelectorAll('select')) {
                const instance = {
                    clear: vi.fn(), clearOptions: vi.fn(), addOption: vi.fn(), refreshOptions: vi.fn(),
                    disable: vi.fn(), enable: vi.fn(), setValue: vi.fn(),
                };
                select.tomselect = instance;
            }
        }
        return { educationSelect, status: document.querySelector('[name="status_pegawai"]') };
    }

    it('loads PNS ranks and retains a selected option only when returned', async () => {
        const { educationSelect } = setupRank();
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, json: async () => [{ id: 2, kode: 'III/a', nama: 'Rank' }] }));
        initEmployeeRank();
        await vi.waitFor(() => expect(document.getElementById('id_pangkat').options).toHaveLength(2));
        expect(fetch).toHaveBeenCalledWith('/ranks/S1?status_pegawai=PNS', expect.objectContaining({ headers: expect.objectContaining({ Accept: 'application/json' }) }));
        expect(document.getElementById('id_pangkat').value).toBe('2');
        expect(document.getElementById('id_pangkat').disabled).toBe(false);
        educationSelect.value = '';
        educationSelect.dispatchEvent(new Event('change'));
        expect(document.getElementById('id_pangkat').disabled).toBe(true);
    });

    it('handles PPPK, unknown statuses, missing config, malformed responses, HTTP errors, and select widgets', async () => {
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
        const { status } = setupRank('PPPK');
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, json: async () => [{ kode: 'III', nama: 'Golongan' }] }));
        initEmployeeRank();
        await vi.waitFor(() => expect(document.getElementById('kode_pppk').options).toHaveLength(2));
        expect(document.getElementById('id_pangkat').value).toBe('0');
        expect(document.getElementById('kode_pppk').value).toBe('III');

        status.value = 'OTHER';
        status.dispatchEvent(new Event('change'));
        await vi.waitFor(() => expect(document.getElementById('kode_pppk').disabled).toBe(true));

        const fixture = setupRank('PNS');
        document.getElementById('tingkat_pendidikan').dataset.ranksUrl = '';
        initEmployeeRank();
        expect(consoleError).toHaveBeenCalledWith('URL pangkat berdasarkan pendidikan tidak ditemukan.');
        expect(fixture.educationSelect.value).toBe('S1');

        const { status: tomStatus } = setupRank('PNS', 'S1', true);
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, json: async () => [{ id: 2, kode: 'III/a', nama: 'Rank' }] }));
        initEmployeeRank();
        await vi.waitFor(() => expect(document.getElementById('id_pangkat').tomselect.setValue).toHaveBeenCalledWith('2', true));
        expect(document.getElementById('kode_pppk').tomselect.setValue).toHaveBeenCalledWith('0', true);
        tomStatus.value = 'PPPK';
        tomStatus.dispatchEvent(new Event('change'));

        setupRank('PPPK', 'S1', true);
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, json: async () => [{ kode: 'III', nama: 'Golongan' }] }));
        initEmployeeRank();
        await vi.waitFor(() => expect(document.getElementById('kode_pppk').tomselect.setValue).toHaveBeenCalledWith('III', true));
    });

    it('resets both selectors when fetch fails or payload is not an array', async () => {
        const errors = vi.spyOn(console, 'error').mockImplementation(() => {});
        setupRank();
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false, status: 500 }));
        initEmployeeRank();
        await vi.waitFor(() => expect(document.getElementById('id_pangkat').options[0].textContent).toContain('Gagal memuat'));
        expect(errors).toHaveBeenCalled();

        setupRank();
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, json: async () => ({ invalid: true }) }));
        initEmployeeRank();
        await vi.waitFor(() => expect(document.getElementById('kode_pppk').options[0].textContent).toContain('Gagal memuat'));
    });
});
