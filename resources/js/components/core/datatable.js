let dataTablePromise = null;
let tableObserver = null;

function loadDataTable() {
    if (!dataTablePromise) {
        dataTablePromise = import('simple-datatables')
            .then(module => module.DataTable)
            .catch(error => {
                dataTablePromise = null;
                console.error(
                    'Gagal memuat Simple DataTables:',
                    error
                );
                return null;
            });
    }
    return dataTablePromise;
}

function findTables(root) {
    const tables = [];
    if (
        root.nodeType === Node.ELEMENT_NODE &&
        root.matches('.js-datatable')
    ) {
        tables.push(root);
    }
    tables.push(
        ...root.querySelectorAll('.js-datatable')
    );
    return tables;
}

function observeDynamicTables() {
    if (
        tableObserver ||
        !document.body ||
        typeof MutationObserver === 'undefined'
    ) {
        return;
    }
    tableObserver = new MutationObserver(records => {
        records.forEach(record => {
            record.addedNodes.forEach(node => {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }
                if (findTables(node).length) {
                    void initDataTablesAuto(node);
                }
            });
        });
    });
    tableObserver.observe(document.body, {
        childList: true,
        subtree: true
    });
}

export async function initDataTablesAuto(root = document) {
    observeDynamicTables();
    const tables = findTables(root);
    if (!tables.length) {
        return;
    }
    const DataTable = await loadDataTable();
    if (!DataTable) {
        return;
    }
    tables.forEach(table => {
        if (
            table.dataset.datatableInitialized === 'true'
        ) {
            return;
        }
        try {
            const hideColumns =
                table.dataset.hide
                    ? table.dataset.hide
                        .split(',')
                        .map(index =>
                            parseInt(index, 10)
                        )
                        .filter(
                            index =>
                                !Number.isNaN(index)
                        )
                    : [];
            const perPage =
                table.dataset.perpage
                    ? parseInt(
                        table.dataset.perpage,
                        10
                    )
                    : 10;
            new DataTable(table, {
                searchable: true,
                sortable: true,
                perPage,
                perPageSelect: [
                    5,
                    10,
                    25,
                    50
                ],
                labels: {
                    placeholder: 'Cari data...',
                    noRows: 'Data tidak ditemukan',
                    info: 'Menampilkan {start}-{end} dari {rows}',
                },
                columns: hideColumns.map(
                    index => ({
                        select: index,
                        sortable: false
                    })
                )
            });
            table.dataset.datatableInitialized =
                'true';
        } catch (error) {
            console.error(
                'Gagal menginisialisasi Simple DataTables:',
                error
            );
        }
    });
    styleDataTable(document);
}
/**
 * Style input dan selector DataTable
 */
function styleDataTable(root) {
    root
        .querySelectorAll('.datatable-input')
        .forEach(el => {
            el.classList.add(
                'block',
                'w-full',
                'md:w-80',
                'p-2.5',
                'text-sm',
                'bg-white',
                'border',
                'border-gray-200',
                'rounded-xl',
                'shadow-sm',
                'focus:ring-2',
                'focus:ring-emerald-500',
                'focus:border-emerald-500'
            );
        });
    root
        .querySelectorAll('.datatable-selector')
        .forEach(el => {
            el.classList.add(
                'bg-white',
                'border',
                'border-gray-200',
                'text-sm',
                'rounded-xl',
                'px-3',
                'py-2',
                'shadow-sm',
                'focus:ring-2',
                'focus:ring-emerald-500',
                'focus:border-emerald-500'
            );
        });
}
