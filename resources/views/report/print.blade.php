<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>
        KGB_{{ \Illuminate\Support\Str::slug($employee->nama, '_') }}
    </title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page {
            size: 210mm 330mm;
            margin: 0;
        }
        html,
        body {
            margin: 0;
            padding: 0;
            background: white;
        }
        body {
            width: 210mm;
            min-height: 330mm;
        }
        .no-print {
            display: none !important;
        }
        @media print {
            html,
            body {
                width: 210mm;
                min-height: 330mm;
            }
            .editable {
                background: transparent !important;
                outline: none !important;
            }
        }
    </style>
</head>
<body>
    @include('report.surat')
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
