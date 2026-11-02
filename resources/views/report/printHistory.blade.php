<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>
        KGB_{{ $history->id }}_{{ \Illuminate\Support\Str::slug($employee->nama, '_') }}_{{ now()->format('Ymd_His') }}
    </title>
    {{-- JQuery tidak diperlukan karena sudah tersedia melalui Vite app.js --}}
    {{-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page {
            size: 210mm 330mm;
        }
        body {
            margin: 0;
            background: white;
        }
        @media print {
            body {
                width: 210mm;
                height: 330mm;
            }
            .no-print {
                display: none !important;
            }
            .editable {
                background: transparent !important;
            }
        }
    </style>
</head>
<body data-print="true" data-history-print="true">
    <input type="hidden" id="employee-id" value="{{ $employee->nip }}">
    <input type="hidden" id="history-id" value="{{ $history->id }}">
    <div id="surat-print">
        @include('report.suratHistory')
    </div>
    <script>
        /*
        |--------------------------------------------------------------------------
        | URL redirect setelah print history
        |--------------------------------------------------------------------------
        */
        window.historyKgbUrl = @json(route('history.kgb'));
        /*
        |--------------------------------------------------------------------------
        | Terapkan data hasil edit Preview History
        |--------------------------------------------------------------------------
        */
        document.addEventListener('DOMContentLoaded', function () {
            const historyId = @json($history->id);
            /*
            |--------------------------------------------------------------------------
            | Storage harus sama dengan Preview History
            |--------------------------------------------------------------------------
            */
            const storageKey = `surat-history-${historyId}`;
            const savedData = sessionStorage.getItem(storageKey);
            /*
            |--------------------------------------------------------------------------
            | Terapkan hasil edit Preview History
            |--------------------------------------------------------------------------
            */
            if (savedData) {
                try {
                    const data = JSON.parse(savedData);
                    const editableElements = document.querySelectorAll(
                        '#surat-print [contenteditable="true"][data-field]'
                    );
                    editableElements.forEach(element => {
                        const field = element.dataset.field;
                        if (
                            Object.prototype.hasOwnProperty.call(
                                data,
                                field
                            )
                        ) {
                            element.innerHTML = data[field];
                        }
                    });
                } catch (error) {
                    console.error(
                        'Gagal menerapkan data edit sementara:',
                        error
                    );
                }
            }
            /*
            |--------------------------------------------------------------------------
            | SETELAH PRINT SELESAI
            |--------------------------------------------------------------------------
            |
            | Karena halaman ini memiliki data-history-print,
            | app.js tidak menjalankan window.print().
            |
            | Redirect setelah print ditangani oleh app.js.
            |--------------------------------------------------------------------------
            */
            /*
            |--------------------------------------------------------------------------
            | Cetak otomatis
            |--------------------------------------------------------------------------
            */
            setTimeout(function () {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
