@extends('layouts.app')
@section('content')
    <div class="p-6">
        {{-- HEADER --}}
        <div class="bg-white rounded-3xl shadow-sm p-6 mb-6">
            <div class="flex flex-col xl:flex-row xl:justify-between xl:items-start gap-6">
                <div class="flex items-start gap-5">
                    {{-- Tombol Kembali --}}
                    <button type="button" onclick="window.history.back()"
                        class="flex-shrink-0 inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                        aria-label="Kembali ke halaman sebelumnya" title="Kembali">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    {{-- Foto --}}
                    <img src="{{ !$data->foto
                        ? asset('images/default-user.png')
                        : (str_starts_with($data->foto, 'images/')
                            ? asset($data->foto)
                            : asset('storage/' . $data->foto)) }}"
                        alt="{{ $data->nama }}"
                        class="w-24 h-24 rounded-3xl object-cover border-4 border-slate-100 shadow-sm">
                    {{-- Biodata --}}
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold text-slate-800">
                            {{ $data->nama }}
                        </h1>
                        <p class="text-slate-500 mt-1">
                            {{ $data->position?->nama ?? '-' }}
                            @if ($data->department)
                                • {{ $data->department->nama }}
                            @endif
                            • {{ $data->status_pegawai }}
                        </p>
                        <div class="flex flex-wrap gap-2 mt-3">
                            <span
                                class="px-3 py-1 rounded-full text-sm font-medium
                                {{ $data->status == 'Aktif'
                                    ? 'bg-green-100 text-green-700'
                                    : ($data->status == 'Nonaktif'
                                        ? 'bg-yellow-100 text-yellow-700'
                                        : 'bg-red-100 text-red-700') }}">
                                {{ $data->status }}
                            </span>
                            @if ($data->rank)
                                <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-sm font-medium">
                                    {{ $data->rank->kode }}
                                    {{ $data->rank->nama }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Ringkasan --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-6">
            {{-- Umur --}}
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-3xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">
                            Umur
                        </p>
                        <h3 class="text-3xl font-bold mt-2">
                            {{ \Carbon\Carbon::parse($data->tanggal_lahir)->age }}
                        </h3>
                        <p class="text-sm opacity-90">
                            Tahun
                        </p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center">
                        <i class="fa-solid fa-cake-candles text-2xl"></i>
                    </div>
                </div>
            </div>
            {{-- Kenaikan Gaji Berkala --}}
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-3xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">
                            TMT KGB
                        </p>
                        <h3 class="text-lg font-bold mt-2">
                            {{ $data->tmt_kgb_baru ? \Carbon\Carbon::parse($data->tmt_kgb_baru)->translatedFormat('d M Y') : '-' }}
                        </h3>
                        @if ($data->tmt_kgb_baru)
                            <p class="text-sm opacity-90">
                                {{ \Carbon\Carbon::parse($data->tmt_kgb_baru)->diffForHumans() }}
                            </p>
                        @endif
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center">
                        <i class="fa-solid fa-money-bill-wave text-2xl"></i>
                    </div>
                </div>
            </div>
            {{-- Pangkat --}}
            @if ($data->tmt_pangkat_baru)
                <div class="bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-3xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">
                                TMT Pangkat
                            </p>
                            <h3 class="text-lg font-bold mt-2">
                                {{ \Carbon\Carbon::parse($data->tmt_pangkat_baru)->translatedFormat('d M Y') }}
                            </h3>
                            <p class="text-sm opacity-90">
                                {{ \Carbon\Carbon::parse($data->tmt_pangkat_baru)->diffForHumans() }}
                            </p>
                        </div>
                        <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center">
                            <i class="fa-solid fa-medal text-2xl"></i>
                        </div>
                    </div>
                </div>
            @endif
            {{-- Pensiun --}}
            <div class="bg-gradient-to-r from-rose-500 to-red-600 text-white rounded-3xl p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">
                            TMT Pensiun
                        </p>
                        <h3 class="text-lg font-bold mt-2">
                            {{ $data->tmt_pensiun ? \Carbon\Carbon::parse($data->tmt_pensiun)->translatedFormat('d M Y') : '-' }}
                        </h3>
                        @if ($data->tmt_pensiun)
                            <p class="text-sm opacity-90">
                                {{ \Carbon\Carbon::parse($data->tmt_pensiun)->diffForHumans() }}
                            </p>
                        @endif
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center">
                        <i class="fa-solid fa-user-clock text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
        {{-- GRID --}}
        <div class="grid lg:grid-cols-2 gap-6">
            {{-- INFORMASI PRIBADI --}}
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-100">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 flex items-center justify-center">
                        <i class="fa-solid fa-id-card text-blue-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">
                            Informasi Pribadi
                        </h3>
                        <p class="text-sm text-slate-500">
                            Data identitas pegawai.
                        </p>
                    </div>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-slate-500 mb-1">NIK</p>
                        <p class="font-semibold text-slate-800">{{ $data->nik ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">NIP</p>
                        <p class="font-semibold text-slate-800">{{ $data->nip ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Tempat Lahir</p>
                        <p class="font-semibold text-slate-800">
                            {{ $data->birthplace?->nama ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Tanggal Lahir</p>
                        <p class="font-semibold text-slate-800">
                            {{ $data->tanggal_lahir ? \Carbon\Carbon::parse($data->tanggal_lahir)->translatedFormat('d F Y') : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Jenis Kelamin</p>
                        <p class="font-semibold text-slate-800">
                            {{ $data->jenis_kelamin ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Agama</p>
                        <p class="font-semibold text-slate-800">
                            {{ $data->agama ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Email</p>
                        <p class="font-semibold text-slate-800 break-all">
                            {{ $data->email ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Nomor HP</p>
                        <p class="font-semibold text-slate-800">
                            {{ $data->no_hp ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Status Pernikahan</p>
                        <p class="font-semibold text-slate-800">
                            {{ $data->status_nikah ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-1">Umur</p>
                        <p class="font-semibold text-slate-800">
                            {{ \Carbon\Carbon::parse($data->tanggal_lahir)->age }} Tahun
                        </p>
                    </div>
                </div>
            </div>
            {{-- DATA KEPEGAWAIAN --}}
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-100">
                    <div class="w-12 h-12 rounded-2xl bg-green-100 flex items-center justify-center">
                        <i class="fa-solid fa-briefcase text-green-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">
                            Data Kepegawaian
                        </h3>
                        <p class="text-sm text-slate-500">
                            Informasi jabatan dan status pegawai.
                        </p>
                    </div>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-slate-500">Unit Kerja</p>
                        <p class="font-semibold">
                            {{ $data->department?->nama ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Jabatan</p>
                        <p class="font-semibold">
                            {{ $data->position?->nama ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">
                            {{ $data->status_pegawai === 'PNS' ? 'Golongan' : 'Pangkat' }}
                        </p>
                        <p class="font-semibold">
                            @if ($data->status_pegawai === 'PNS')
                                {{ $data->rank ? $data->rank->nama . ' (' . $data->rank->kode . ')' : '-' }}
                            @else
                                {{ $data->pppk->nama ?? '-' }}
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Status Pegawai</p>
                        <p class="font-semibold">
                            {{ $data->status_pegawai ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Status</p>
                        <p class="font-semibold">
                            {{ $data->status ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Pendidikan Terakhir</p>
                        <p class="font-semibold">
                            {{ $data->educationLevel?->nama ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Usia Pensiun</p>
                        <p class="font-semibold">
                            {{ $data->position->usia_pensiun ?? '-' }} Tahun
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Tanggal Masuk</p>
                        <p class="font-semibold">
                            {{ $data->tanggal_masuk ? \Carbon\Carbon::parse($data->tanggal_masuk)->translatedFormat('d F Y') : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">
                            Masa Kerja Keseluruhan
                        </p>
                        <p class="font-semibold">
                            @if ($data->tanggal_masuk && \Carbon\Carbon::parse($data->tanggal_masuk)->lte(now()))
                                @php
                                    $masaKerja = \Carbon\Carbon::parse($data->tanggal_masuk)
                                        ->startOfDay()
                                        ->diff(now()->startOfDay());
                                    $tahun = $masaKerja->y;
                                    $bulan = $masaKerja->m;
                                @endphp
                                {{ $tahun }} tahun {{ $bulan }} bulan
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">
                            Masa Kerja Golongan
                        </p>
                        <p class="font-semibold">
                            {{ $data->tahun_masa_kerja }} Tahun
                            {{ $data->bulan_masa_kerja }} Bulan
                        </p>
                    </div>
                    @if ($data->tmt_pangkat_lama)
                        <div>
                            <p class="text-sm text-slate-500">TMT Pangkat Lama</p>
                            <p class="font-semibold">
                                {{ $data->tmt_pangkat_lama ? \Carbon\Carbon::parse($data->tmt_pangkat_lama)->translatedFormat('d F Y') : '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">TMT Pangkat Baru</p>
                            <p class="font-semibold">
                                {{ $data->tmt_pangkat_baru ? \Carbon\Carbon::parse($data->tmt_pangkat_baru)->translatedFormat('d F Y') : '-' }}
                            </p>
                        </div>
                    @endif
                    <div>
                        <p class="text-sm text-slate-500">TMT KGB Lama</p>
                        <p class="font-semibold">
                            {{ $data->tmt_kgb_lama ? \Carbon\Carbon::parse($data->tmt_kgb_lama)->translatedFormat('d F Y') : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">TMT KGB Baru</p>
                        <p class="font-semibold">
                            {{ $data->tmt_kgb_baru ? \Carbon\Carbon::parse($data->tmt_kgb_baru)->translatedFormat('d F Y') : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Tanggal Pensiun</p>
                        <p class="font-semibold">
                            {{ $data->tanggal_pensiun ? \Carbon\Carbon::parse($data->tanggal_pensiun)->translatedFormat('d F Y') : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">TMT Pensiun</p>
                        <p class="font-semibold">
                            {{ $data->tmt_pensiun ? \Carbon\Carbon::parse($data->tmt_pensiun)->translatedFormat('d F Y') : '-' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- ALAMAT --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 m-6">
        <h3 class="text-xl font-bold mb-6">
            Alamat
        </h3>
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-slate-500">Provinsi</p>
                <p class="font-semibold">{{ $data->province?->nama ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">Kabupaten / Kota</p>
                <p class="font-semibold">{{ $data->city?->nama ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">Kecamatan</p>
                <p class="font-semibold">{{ $data->district?->nama ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">Kelurahan / Desa</p>
                <p class="font-semibold">{{ $data->urbanVillage?->nama ?? '-' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-sm text-slate-500">Alamat Lengkap</p>
                <p class="font-semibold leading-7">
                    {{ $data->alamat ?? '-' }}
                </p>
            </div>
        </div>
    </div>
    {{-- DOKUMEN KEPEGAWAIAN --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 m-6">
        @php
            $jenisDokumen = [
                'SK_KGB' => [
                    'label' => 'SK Kenaikan Gaji Berkala (KGB)',
                    'icon' => 'fa-money-bill-wave',
                    'warna' => 'green',
                ],
                'SK_PANGKAT' => [
                    'label' => 'SK Pangkat Terakhir',
                    'icon' => 'fa-medal',
                    'warna' => 'blue',
                ],
                'SK_JABATAN' => [
                    'label' => 'SK Jabatan Terakhir',
                    'icon' => 'fa-briefcase',
                    'warna' => 'purple',
                ],
                'IJAZAH_TERAKHIR' => [
                    'label' => 'Ijazah Terakhir',
                    'icon' => 'fa-graduation-cap',
                    'warna' => 'amber',
                ],
                'SK_PENGANGKATAN_PNS' => [
                    'label' => 'SK Pengangkatan PNS',
                    'icon' => 'fa-file-signature',
                    'warna' => 'indigo',
                ],
                'SK_PENGANGKATAN_PPPK' => [
                    'label' => 'SK Pengangkatan PPPK',
                    'icon' => 'fa-file-signature',
                    'warna' => 'indigo',
                ],
                'KTP' => [
                    'label' => 'Kartu Tanda Penduduk (KTP)',
                    'icon' => 'fa-id-card',
                    'warna' => 'rose',
                ],
            ];
            $dokumenTersimpan = $data->dokumen->keyBy('jenis_dokumen');
            $jumlahFileTersedia = $dokumenTersimpan
                ->filter(fn ($dokumen) => filled($dokumen->nama_file)
                    && \Illuminate\Support\Facades\Storage::disk('local')
                        ->exists($dokumen->nama_file))
                ->count();
        @endphp
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div>
                <h3 class="text-xl font-bold text-slate-800">
                    Dokumen Kepegawaian
                </h3>
                <p class="text-sm text-slate-500 mt-1">
                    Arsip dokumen pendukung dan validasi data pegawai.
                </p>
            </div>
            <span
                class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-sm font-medium text-slate-600">
                <i class="fa-solid fa-folder-open"></i>
                {{ $jumlahFileTersedia }} dari {{ count($jenisDokumen) }} dokumen
            </span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($jenisDokumen as $jenis => $info)
                @php
                    $dokumen = $dokumenTersimpan->get($jenis);
                    $fileTersedia = $dokumen
                        && filled($dokumen->nama_file)
                        && \Illuminate\Support\Facades\Storage::disk('local')
                            ->exists($dokumen->nama_file);
                @endphp
                <div class="rounded-2xl border border-slate-200 p-4 transition hover:border-blue-300 hover:shadow-sm">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                            <i class="fa-solid {{ $info['icon'] }} text-lg"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-semibold text-slate-800 text-sm leading-5">
                                {{ $info['label'] }}
                            </h4>
                            @if ($fileTersedia)
                                <span
                                    class="inline-flex items-center gap-1 mt-2 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                    <i class="fa-solid fa-circle-check"></i>
                                    Tersedia
                                </span>
                            @elseif ($dokumen)
                                <span
                                    class="inline-flex items-center gap-1 mt-2 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    File tidak ditemukan
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-1 mt-2 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">
                                    <i class="fa-solid fa-minus-circle"></i>
                                    Belum diunggah
                                </span>
                            @endif
                        </div>
                    </div>
                    @if ($dokumen)
                        <div class="mt-4 border-t border-slate-100 pt-3">
                            <p class="text-xs text-slate-500 mb-1">
                                Nama file
                            </p>
                            <p class="text-sm text-slate-700 break-all">
                                {{ $dokumen->nama_file_asli }}
                            </p>
                            <p class="mt-2 text-xs text-slate-500">
                                Diunggah:
                                {{ $dokumen->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Pengunggah:
                                {{ $dokumen->user->nama ?? 'Tidak Diketahui' }}
                            </p>
                            @if ($fileTersedia)
                                <div class="mt-4">
                                <a href="{{ route('employee.documents.show', [
                                    'employee' => $data->nip,
                                    'document' => $dokumen->id,
                                ]) }}"
                                    target="_blank" rel="noopener noreferrer"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">
                                    <i class="fa-solid fa-eye"></i>
                                    Lihat Dokumen
                                </a>
                                </div>
                            @else
                                <p class="mt-4 text-sm text-amber-700">
                                    File dokumen tidak ditemukan di penyimpanan.
                                </p>
                            @endif
                        </div>
                    @else
                        <div class="mt-4 border-t border-slate-100 pt-3">
                            <p class="text-xs leading-5 text-slate-400">
                                Belum ada file untuk kategori ini.
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 m-6">
        <h3 class="text-xl font-bold mb-6">
            Informasi Sistem
        </h3>
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-slate-500">
                    Dibuat
                </p>
                <p class="font-semibold">
                    {{ $data->created_at->translatedFormat('d F Y H:i') }}
                </p>
            </div>
            <div>
                <p class="text-sm text-slate-500">
                    Terakhir Diubah
                </p>
                <p class="font-semibold">
                    {{ $data->updated_at->translatedFormat('d F Y H:i') }}
                </p>
            </div>
        </div>
    </div>
    </div>
@endsection
