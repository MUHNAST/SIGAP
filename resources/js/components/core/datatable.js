import { DataTable } from 'simple-datatables';
export function initDataTablesAuto() {
    const tables =
        document.querySelectorAll(
            '.js-datatable'
        );
    if (!tables.length) {
        return;
    }
    tables.forEach(table => {
        if (
            table.dataset.datatableInitialized === 'true'
        ) {
            return;
        }
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
    });
    styleDataTable();
}
/**
 * Style input dan selector DataTable
 */
function styleDataTable() {
    document
        .querySelectorAll(
            '.datatable-input'
        )
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
    document
        .querySelectorAll(
            '.datatable-selector'
        )
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
