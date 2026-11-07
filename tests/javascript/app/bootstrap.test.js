import { beforeEach, describe, expect, it, vi } from 'vitest';

const axios = vi.hoisted(() => ({
    defaults: {
        headers: {
            common: {},
        },
    },
}));

vi.mock('axios', () => ({ default: axios }));

describe('Axios browser bootstrap', () => {
    beforeEach(() => {
        delete window.axios;
        delete axios.defaults.headers.common['X-Requested-With'];
    });

    it('exposes Axios globally and configures the XMLHttpRequest header', async () => {
        await import('../../../resources/js/bootstrap.js');
        expect(window.axios).toBe(axios);
        expect(window.axios.defaults.headers.common['X-Requested-With']).toBe('XMLHttpRequest');
    });
});
