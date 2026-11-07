import { beforeEach, describe, expect, it, vi } from 'vitest';
import { initModalValidation } from '../../../resources/js/components/modules/modal-validation.js';
import { initPasswordToggle } from '../../../resources/js/components/modules/password-toggle.js';
import { initImagePreview } from '../../../resources/js/components/modules/photo-preview.js';
import { initRolePermissions } from '../../../resources/js/components/modules/role-permissions.js';
import { initSettingTabs, showTab } from '../../../resources/js/components/modules/settings-tab.js';
import { initWelcomeCard } from '../../../resources/js/components/modules/welcome-card.js';

beforeEach(() => {
    document.body.innerHTML = '';
    sessionStorage.clear();
    delete window.activeTabFromServer;
    vi.useRealTimers();
    vi.unstubAllGlobals();
});

describe('modal validation', () => {
    it('reopens the previous modal only when errors, session key, and modal exist', () => {
        document.body.innerHTML = '<div id="modal" class="hidden"></div>';
        document.body.dataset.validationErrors = 'false';
        sessionStorage.setItem('activeModal', 'modal');
        initModalValidation();
        expect(document.getElementById('modal').classList.contains('hidden')).toBe(true);
        document.body.dataset.validationErrors = 'true';
        sessionStorage.removeItem('activeModal');
        initModalValidation();
        sessionStorage.setItem('activeModal', 'missing');
        initModalValidation();
        document.getElementById('modal').classList.remove('hidden');
        sessionStorage.setItem('activeModal', 'modal');
        initModalValidation();
        expect(document.getElementById('modal').classList.contains('flex')).toBe(true);
        expect(document.getElementById('modal').classList.contains('hidden')).toBe(false);
    });
});

describe('password visibility toggle', () => {
    it('switches input types and icon state, and reports missing controls', () => {
        document.body.innerHTML = '<input id="password" type="password"><button id="toggle"><i class="fa-eye"></i></button>';
        initPasswordToggle();
        const input = document.getElementById('password');
        const icon = document.querySelector('i');
        window.togglePassword('password', document.getElementById('toggle'));
        expect(input.type).toBe('text');
        expect(icon.classList.contains('fa-eye')).toBe(false);
        expect(icon.classList.contains('fa-eye-slash')).toBe(true);
        window.togglePassword('password', document.getElementById('toggle'));
        expect(input.type).toBe('password');
        expect(icon.classList.contains('fa-eye')).toBe(true);
        const error = vi.spyOn(console, 'error').mockImplementation(() => {});
        window.togglePassword('missing', document.getElementById('toggle'));
        window.togglePassword('password', null);
        expect(error).toHaveBeenCalledTimes(2);
    });
});

describe('image preview validation and cleanup', () => {
    it('previews valid images, revokes replaced URLs, and restores defaults on cancel/rejection', () => {
        document.body.innerHTML = '<img id="preview" src="default.png"><span id="filename"></span><input id="photo" class="image-preview-input" data-preview="#preview" data-filename="#filename" type="file">';
        const input = document.getElementById('photo');
        const image = document.getElementById('preview');
        const filename = document.getElementById('filename');
        const create = vi.fn()
            .mockReturnValueOnce('blob:first')
            .mockReturnValueOnce('blob:second');
        const revoke = vi.fn();
        vi.stubGlobal('URL', { createObjectURL: create, revokeObjectURL: revoke });
        const alert = vi.spyOn(window, 'alert').mockImplementation(() => {});
        const change = (file) => {
            Object.defineProperty(input, 'files', { configurable: true, value: file ? [file] : [] });
            input.dispatchEvent(new Event('change'));
        };
        initImagePreview();
        change({ type: 'image/jpeg', size: 12, name: 'first.jpg' });
        expect(image.src).toContain('blob:first');
        expect(filename.textContent).toBe('first.jpg');
        change({ type: 'image/png', size: 14, name: 'second.png' });
        expect(revoke).toHaveBeenCalledWith('blob:first');
        expect(image.src).toContain('blob:second');
        change({ type: 'image/gif', size: 1, name: 'invalid.gif' });
        expect(alert).toHaveBeenCalledWith('Format file harus JPG, JPEG, atau PNG.');
        expect(image.src).toBe('http://localhost:3000/default.png');
        change({ type: 'image/jpeg', size: 2 * 1024 * 1024 + 1, name: 'large.jpg' });
        expect(alert).toHaveBeenLastCalledWith('Ukuran foto maksimal 2 MB.');
        expect(input.value).toBe('');
        change(null);
        expect(filename.innerHTML).toContain('Maksimal 2 MB');
        initImagePreview();
        expect(input.dataset.initialized).toBe('true');
    });

    it('leaves inputs untouched when preview target metadata is missing', () => {
        document.body.innerHTML = '<input class="image-preview-input"><input class="image-preview-input" data-preview="#missing">';
        initImagePreview();
        expect([...document.querySelectorAll('input')].every(input => input.dataset.initialized !== 'true')).toBe(true);
    });
});

describe('settings tabs', () => {
    function setupTabs() {
        document.body.innerHTML = `
          <button id="tab-account"></button><button id="tab-security"></button>
          <section id="account-tab" class="hidden"></section><section id="security-tab" class="hidden"></section>`;
    }

    it('shows only supported tabs, falls back from stale notification state, and prefers server state', () => {
        setupTabs();
        sessionStorage.setItem('active_tab', 'notification');
        initSettingTabs();
        expect(document.getElementById('account-tab').classList.contains('hidden')).toBe(false);
        expect(document.getElementById('security-tab').classList.contains('hidden')).toBe(true);
        expect(sessionStorage.getItem('active_tab')).toBe('account');
        window.activeTabFromServer = 'security';
        initSettingTabs();
        expect(document.getElementById('security-tab').classList.contains('hidden')).toBe(false);
        showTab('unsupported');
        expect(sessionStorage.getItem('active_tab')).toBe('account');
        expect(window.showTab).toBe(showTab);
    });

    it('does not initialize without the Settings tab controls and tolerates missing panel/button', () => {
        expect(() => initSettingTabs()).not.toThrow();
        document.body.innerHTML = '<button id="tab-account"></button>';
        expect(() => showTab('security')).not.toThrow();
    });
});

describe('role permission form interactions', () => {
    function permissionMarkup() {
        document.body.innerHTML = `
          <form id="role-permission-form">
            <input id="permission-search"><button type="button" id="select-all-permissions"></button>
            <span id="active-permission-count"></span><button type="submit"></button>
            <section class="permission-module" data-module="pegawai">
              <input class="module-select" type="checkbox"><span class="module-summary"></span>
              <div class="permission-item" data-search="pegawai.view lihat data"><input class="permission-checkbox" type="checkbox"></div>
              <div class="permission-item" data-search="pegawai.update ubah data"><input class="permission-checkbox" type="checkbox"></div>
            </section>
            <section class="permission-module" data-module="laporan">
              <input class="module-select" type="checkbox"><span class="module-summary"></span>
              <div class="permission-item" data-search="laporan.view"><input class="permission-checkbox" type="checkbox"></div>
            </section>
          </form>`;
    }

    it('synchronizes module/all checkboxes, searches metadata, and disables duplicate submit', () => {
        permissionMarkup();
        const form = document.getElementById('role-permission-form');
        initRolePermissions();
        const modules = document.querySelectorAll('.permission-module');
        const [moduleSelect, ...moduleInputs] = modules[0].querySelectorAll('input');
        moduleSelect.checked = true;
        moduleSelect.dispatchEvent(new Event('change'));
        expect([...moduleInputs].every(input => input.checked)).toBe(true);
        expect(moduleSelect.indeterminate).toBe(false);
        expect(modules[0].querySelector('.module-summary').textContent).toBe('2 dari 2 izin aktif');
        expect(document.getElementById('active-permission-count').textContent).toBe('2');
        moduleInputs[0].checked = false;
        moduleInputs[0].dispatchEvent(new Event('change'));
        expect(moduleSelect.indeterminate).toBe(true);
        expect(document.getElementById('select-all-permissions').innerHTML).toContain('Pilih Semua');
        document.getElementById('permission-search').value = ' LAPORAN ';
        document.getElementById('permission-search').dispatchEvent(new Event('input'));
        expect(modules[0].classList.contains('hidden')).toBe(true);
        expect(modules[1].classList.contains('hidden')).toBe(false);
        const submit = new Event('submit', { bubbles: true });
        form.dispatchEvent(submit);
        expect(form.querySelector('button[type="submit"]').disabled).toBe(true);
        expect(form.querySelector('button[type="submit"]').textContent).toContain('Menyimpan');
        expect(form.querySelector('button[type="submit"]').classList.contains('cursor-wait')).toBe(true);
        document.getElementById('select-all-permissions').click();
        expect([...document.querySelectorAll('.permission-checkbox')].every(input => input.checked)).toBe(true);
        document.getElementById('select-all-permissions').click();
        expect([...document.querySelectorAll('.permission-checkbox')].every(input => !input.checked)).toBe(true);
    });

    it('ignores absent and already initialized forms', () => {
        expect(() => initRolePermissions()).not.toThrow();
        permissionMarkup();
        const form = document.getElementById('role-permission-form');
        const checkbox = form.querySelector('.permission-checkbox');
        const addListener = vi.spyOn(checkbox, 'addEventListener');
        initRolePermissions();
        expect(addListener).toHaveBeenCalledWith('change', expect.any(Function));
        expect(form.dataset.initialized).toBe('true');
        expect(() => initRolePermissions()).not.toThrow();
        expect(addListener).toHaveBeenCalledOnce();
    });
});

describe('welcome card', () => {
    it('hides the welcome card after its two-stage delay and skips missing cards', () => {
        vi.useFakeTimers();
        expect(() => initWelcomeCard()).not.toThrow();
        document.body.innerHTML = '<div id="welcomeCard"></div>';
        const card = document.getElementById('welcomeCard');
        initWelcomeCard();
        vi.advanceTimersByTime(7999);
        expect(card.classList.contains('opacity-0')).toBe(false);
        vi.advanceTimersByTime(1);
        expect(card.classList.contains('opacity-0')).toBe(true);
        vi.advanceTimersByTime(500);
        expect(card.classList.contains('hidden')).toBe(true);
    });
});
