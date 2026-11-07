import $ from 'jquery';
let tomSelectPromise = null;
const pendingInstances = new WeakMap();

function loadTomSelect() {
    if (!tomSelectPromise) {
        tomSelectPromise = Promise.all([
            import('tom-select'),
            import('tom-select/dist/css/tom-select.css')
        ])
            .then(([module]) => module.default)
            .catch(error => {
                tomSelectPromise = null;
                console.error(
                    'Gagal memuat TomSelect:',
                    error
                );
                return null;
            });
    }
    return tomSelectPromise;
}

// ===============================
// TOMSELECT SEARCHABLE DROPDOWN
// ===============================
async function searchableSelect(
    el,
    placeholder = 'Ketik untuk mencari...'
) {
    if (!el) {
        return null;
    }
    // Hindari instance ganda
    if (el.tomselect) {
        return el.tomselect;
    }
    if (pendingInstances.has(el)) {
        return pendingInstances.get(el);
    }
    const pending = (async () => {
        const TomSelect = await loadTomSelect();
        if (!TomSelect || el.tomselect) {
            return el.tomselect || null;
        }
        const ts = new TomSelect(el, {
            create: false,
            persist: false,
            allowEmptyOption: true,
            maxOptions: null,
            searchField: ['text'],
            placeholder,
            hidePlaceholder: true,
            openOnFocus: true,
            render: {
                option(data, escape) {
                    return `
                    <div>
                        ${escape(data.text)}
                    </div>
                `;
                }
            },
            onInitialize() {
                const selected =
                    this.input.dataset.selected;
                if (
                    selected !== undefined &&
                    selected !== null &&
                    selected !== ''
                ) {
                    this.setValue(
                        String(selected),
                        true
                    );
                }
            }
        });
        return ts;
    })();
    pendingInstances.set(el, pending);
    try {
        return await pending;
    } finally {
        pendingInstances.delete(el);
    }
}
// ===============================
// RESET SELECT
// ===============================
function resetSelect(
    el,
    placeholderText
) {
    if (!el?.tomselect) {
        return;
    }
    const ts = el.tomselect;
    ts.clear(true);
    ts.clearOptions();
    ts.addOption({
        value: '',
        text: placeholderText
    });
    ts.refreshOptions(false);
    // Pastikan bisa dipilih kembali
    ts.enable();
}
// ===============================
// LOAD OPTIONS AJAX
// ===============================
function loadOptions(
    url,
    selectEl,
    placeholder,
    selected = null
) {
    if (!selectEl?.tomselect) {
        return Promise.resolve();
    }
    const ts =
        selectEl.tomselect;
    // Loading
    ts.clear(true);
    ts.clearOptions();
    ts.addOption({
        value: '',
        text: 'Memuat data...'
    });
    ts.refreshOptions(false);
    // Nonaktif sementara
    ts.disable();
    return $.get(url)
        .then(data => {
            ts.clear(true);
            ts.clearOptions();
            ts.addOption({
                value: '',
                text: placeholder
            });
            if (Array.isArray(data)) {
                data.forEach(item => {
                    ts.addOption({
                        value:
                            String(item.id),
                        text:
                            String(item.nama)
                    });
                });
            }
            ts.refreshOptions(false);
            // Aktifkan kembali setelah data masuk
            ts.enable();
            // EDIT
            if (
                selected !== null &&
                selected !== undefined &&
                selected !== ''
            ) {
                ts.setValue(
                    String(selected),
                    true
                );
            }
        })
        .catch(error => {
            console.error(
                'Gagal memuat data:',
                error
            );
            ts.clear(true);
            ts.clearOptions();
            ts.addOption({
                value: '',
                text: 'Gagal memuat data'
            });
            ts.refreshOptions(false);
            // Jangan biarkan select terkunci
            ts.enable();
        });
}
// ===============================
// EXPORT
// ===============================
export {
    searchableSelect,
    resetSelect,
    loadOptions
};
