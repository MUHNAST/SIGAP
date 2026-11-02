<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Data Pegawai</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 15mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #222;
            margin: 0;
        }

        /* Kop surat */
        .kop-surat {
            width: 100%;
            position: relative;
            border-bottom: 4px double #000;
            padding: 0 0 7px;
            margin-bottom: 12px;
            min-height: 76px;
        }

        .logo {
            position: absolute;
            left: 8mm;
            top: -3mm;
            width: 20mm;
            height: 23mm;
        }

        .kop-teks {
            text-align: center;
            padding: 0 25mm;
        }

        .kop-teks h3,
        .kop-teks h1,
        .kop-teks p {
            margin: 0;
            padding: 0;
            text-align: center;
        }

        .kop-teks h3 {
            font-size: 14px;
            font-weight: normal;
            line-height: 1.3;
        }

        .kop-teks h1 {
            font-size: 18px;
            font-weight: bold;
            line-height: 1.3;
        }

        .kop-teks p {
            font-size: 10px;
            line-height: 1.4;
        }

        /* Judul laporan */
        .judul-laporan {
            text-align: center;
            margin: 14px 0 16px;
        }

        .judul-laporan h2 {
            font-size: 13px;
            margin: 0 0 3px;
            text-transform: uppercase;
        }

        .judul-laporan p {
            font-size: 9px;
            margin: 0;
        }

        /* Judul unit kerja */
        .judul-unit {
            font-size: 10px;
            font-weight: bold;
            margin: 14px 0 6px;
            page-break-after: avoid;
        }

        /* Tabel data */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        .tabel-unit {
            margin-bottom: 12px;
        }

        .tabel-unit thead {
            display: table-header-group;
        }

        .tabel-unit tbody {
            display: table-row-group;
        }

        .tabel-unit tr {
            page-break-inside: avoid;
        }

        th,
        td {
            border: 1px solid #777;
            padding: 5px;
            text-align: left;
            vertical-align: top;
            overflow-wrap: break-word;
        }

        th {
            background-color: #e5e7eb;
            font-weight: bold;
            text-align: center;
        }

        .nomor {
            text-align: center;
            width: 25px;
        }

        /* Informasi pembuat dan tanggal cetak */
        .informasi-cetak {
            width: 100%;
            margin-top: 14px;
            font-size: 9px;
            page-break-inside: avoid;
        }

        .informasi-cetak td {
            border: none;
            padding: 2px 0;
            vertical-align: top;
        }

        .informasi-cetak .label {
            width: 90px;
        }

        .informasi-cetak .titik-dua {
            width: 10px;
            text-align: center;
        }
    </style>
</head>

<body>
    {{-- Kop surat --}}
    <div class="kop-surat">
        <img src="{{ public_path('images/pekalongan.png') }}" alt="Logo Pemerintah Kota Pekalongan" class="logo">
        <div class="kop-teks">
            <h3>PEMERINTAH KOTA PEKALONGAN</h3>
            <h1>KECAMATAN PEKALONGAN TIMUR</h1>
            <p>Jalan Tondano Nomor 19 Telepon (0285) 422328 Pekalongan</p>
            <p>Kode Pos 51122 | Email: kecamatanpekalongantimur@gmail.com</p>
        </div>
    </div>
    {{-- Tabel terpisah untuk setiap unit kerja yang memiliki data --}}
    @forelse ($groups as $group)
        @if (count($group['rows']) > 0)
            <div class="judul-unit">
                Unit Kerja: {{ $group['unit_kerja'] }}
            </div>
            <table class="tabel-unit">
                <thead>
                    <tr>
                        @foreach ($headings as $heading)
                            <th @if ($loop->first) class="nomor" @endif>
                                {{ $heading }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($group['rows'] as $row)
                        <tr>
                            <td class="nomor">{{ $loop->iteration }}</td>
                            @foreach ($row as $value)
                                <td>{{ $value ?? '-' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @empty
        <p style="text-align: center;">
            Tidak ada data pegawai untuk ditampilkan.
        </p>
    @endforelse
    {{-- Informasi pembuat dan tanggal cetak --}}
    <table class="informasi-cetak">
        <tbody>
            <tr>
                <td class="label">Dibuat oleh</td>
                <td class="titik-dua">:</td>
                <td>
                    {{ auth()->user()->name ?? (auth()->user()->nama ?? (auth()->user()->username ?? 'Pengguna')) }}
                </td>
            </tr>
            <tr>
                <td class="label">Tanggal cetak</td>
                <td class="titik-dua">:</td>
                <td>
                    {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>
