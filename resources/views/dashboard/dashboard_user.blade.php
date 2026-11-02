@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <div class="p-4 md:p-6 lg:p-8">
        <!-- WELCOME -->
        @if (session('show_welcome'))
            <div id="welcomeCard"
                class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-3xl p-8 text-white mb-8 shadow-xl transition-all duration-500">
                <h1 class="text-3xl font-bold">
                    {{ session('welcome_message') }} 👋
                </h1>
                <p class="mt-2 opacity-90">
                    Pantau seluruh aktivitas kepegawaian dalam satu dashboard.
                </p>
            </div>
        @endif
        <!-- STATISTIC -->
        @php
            $user = auth()->user();
            $foto = $user->employee?->foto;
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            {{-- Kenaikan Gaji Berkala --}}
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm text-slate-500">
                            Kenaikan Gaji Berkala
                        </p>
                        <h3 class="mt-3 text-2xl font-bold text-slate-800">
                            {{ $kgb['tanggal']?->translatedFormat('d F Y') }}
                        </h3>
                        <div
                            class="inline-flex items-center mt-4 px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-sm font-medium">
                            <i class="fa-solid fa-calendar-days mr-2"></i>
                            @if ($kgb['status'] == 'Hari Ini')
                                Hari Ini
                            @elseif($kgb['status'] == 'Terlambat')
                                Terlambat
                            @else
                                @if ($kgb['tahun_sisa'] > 0)
                                    {{ $kgb['tahun_sisa'] }} Tahun
                                @endif
                                @if ($kgb['bulan_sisa'] > 0)
                                    {{ $kgb['bulan_sisa'] }} Bulan
                                @endif
                                @if ($kgb['hari_sisa'] > 0)
                                    {{ $kgb['hari_sisa'] }} Hari
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="w-16 h-16 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center">
                        <i class="fa-solid fa-money-bill-wave text-2xl"></i>
                    </div>
                </div>
            </div>
            {{-- Kenaikan Pangkat --}}
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm text-slate-500">
                            Kenaikan Pangkat
                        </p>
                        <h3 class="mt-3 text-2xl font-bold text-slate-800">
                            {{ $promotion['tanggal']?->translatedFormat('d F Y') ?? '-' }}
                        </h3>
                        <div
                            class="inline-flex items-center mt-4 px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm font-medium">
                            <i class="fa-solid fa-arrow-trend-up mr-2"></i>
                            <span>
                                @if ($promotion['status'] == 'Hari Ini')
                                    Hari Ini
                                @elseif($promotion['status'] == 'Terlambat')
                                    Terlambat
                                @else
                                    @if ($promotion['tahun_sisa'] > 0)
                                        {{ $promotion['tahun_sisa'] }} Tahun
                                    @endif
                                    @if ($promotion['bulan_sisa'] > 0)
                                        {{ $promotion['bulan_sisa'] }} Bulan
                                    @endif
                                    @if ($promotion['hari_sisa'] > 0)
                                        {{ $promotion['hari_sisa'] }} Hari
                                    @endif
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="w-16 h-16 rounded-2xl bg-green-100 text-green-600 flex items-center justify-center">
                        <i class="fa-solid fa-medal text-2xl"></i>
                    </div>
                </div>
            </div>
            {{-- Masa Pensiun --}}
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm text-slate-500">
                            Masa Pensiun
                        </p>
                        <h3 class="mt-3 text-2xl font-bold text-slate-800">
                            {{ $retirement['tanggal']?->translatedFormat('d F Y') ?? '-' }}
                        </h3>
                        <div
                            class="inline-flex items-center mt-4 px-3 py-1 rounded-full bg-orange-100 text-orange-700 text-sm font-medium">
                            <i class="fa-solid fa-user-clock mr-2"></i>
                            @if ($retirement['status'] == 'Hari Ini')
                                Hari Ini
                            @elseif($retirement['status'] == 'Terlambat')
                                Terlambat
                            @else
                                @if ($retirement['tahun_sisa'] > 0)
                                    {{ $retirement['tahun_sisa'] }} Tahun
                                @endif
                                @if ($retirement['bulan_sisa'] > 0)
                                    {{ $retirement['bulan_sisa'] }} Bulan
                                @endif
                                @if ($retirement['hari_sisa'] > 0)
                                    {{ $retirement['hari_sisa'] }} Hari
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="w-16 h-16 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center">
                        <i class="fa-solid fa-hourglass-end text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- MIDDLE -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-8 mb-8">
            {{-- Tombol di bagian atas sebelah kanan --}}
            {{-- Header Detail Pegawai --}}
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 pb-5 border-b border-slate-100">
                {{-- Judul dan Deskripsi --}}
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fa-solid fa-user-tie text-blue-600"></i>
                        <h3 class="text-lg font-bold text-slate-800">
                            Informasi Pegawai
                        </h3>
                    </div>
                    <p class="text-sm text-slate-500 leading-relaxed">
                        Ringkasan identitas, jabatan, pangkat, dan masa kerja pegawai.
                        Klik tombol di samping untuk melihat informasi selengkapnya.
                    </p>
                </div>
                {{-- Tombol Lihat Detail --}}
                <div class="flex justify-start sm:justify-end flex-shrink-0">
                    <a href="{{ route('employe.detail') }}"
                        class="inline-flex items-center justify-center gap-2 px-5 py-3
                rounded-xl bg-blue-600 text-white font-semibold
                hover:bg-blue-700 focus:outline-none focus:ring-4
                focus:ring-blue-200 transition shadow-sm">
                        <i class="fa-solid fa-eye"></i>
                        <span>Lihat Semua Detail Pegawai</span>
                        <i class="fa-solid fa-arrow-right text-sm"></i>
                    </a>
                </div>
            </div>
            <div class="flex flex-col lg:flex-row gap-8 items-center lg:items-start">
                {{-- Foto dan Identitas --}}
                <div class="flex flex-col items-center">
                    <img src="{{ !$foto || $foto === 'images/default-user.png' ? asset('images/default-user.png') : asset('storage/' . $foto) }}"
                        alt="Foto {{ $employee->nama }}"
                        class="w-28 h-28 rounded-full object-cover border-4 border-blue-100 shadow-lg">
                    <h2 class="mt-4 text-xl font-bold text-slate-800 text-center">
                        {{ $employee->nama }}
                    </h2>
                    <p class="text-sm text-slate-500 text-center">
                        {{ $employee->position?->nama ?? '-' }}
                    </p>
                </div>
                {{-- Informasi Pegawai --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 flex-1 w-full">
                    {{-- NIP --}}
                    <div class="bg-slate-50 rounded-2xl p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                                <i class="fa-solid fa-id-card"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs text-slate-500">NIP</p>
                                <p class="font-semibold text-slate-800 break-words">
                                    {{ $employee->nip }}
                                </p>
                            </div>
                        </div>
                    </div>
                    {{-- Jabatan --}}
                    <div class="bg-slate-50 rounded-2xl p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-green-100 text-green-600 flex items-center justify-center">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Jabatan</p>
                                <p class="font-semibold text-slate-800">
                                    {{ $employee->position?->nama ?? '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    {{-- Pangkat / Golongan --}}
                    <div class="bg-slate-50 rounded-2xl p-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                                <i class="fa-solid fa-medal"></i>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">
                                    {{ $employee->status_pegawai === 'PNS' ? 'Golongan' : 'Pangkat' }}
                                </p>
                                <p class="font-semibold">
                                    @if ($employee->status_pegawai === 'PNS')
                                        {{ $employee->rank ? $employee->rank->nama . ' (' . $employee->rank->kode . ')' : '-' }}
                                    @else
                                        {{ $employee->pppk->nama ?? '-' }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    {{-- Masa Kerja --}}
                    <div class="bg-slate-50 rounded-2xl p-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                                <i class="fa-solid fa-business-time"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Masa Kerja</p>
                                <p class="font-semibold text-slate-800">
                                    {{ $employee->tahun_masa_kerja ?? 0 }} Tahun
                                    {{ sprintf('%02d', $employee->bulan_masa_kerja ?? 0) }} Bulan
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- BOTTOM -->
        <!-- ================= BOTTOM SECTION ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- ================= PENGINGAT ================= --}}
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 h-full">
                {{-- Header --}}
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">
                            Pengingat Penting
                        </h3>
                        <p class="text-sm text-slate-500">
                            KGB, Kenaikan Pangkat, dan Pensiun terdekat
                        </p>
                    </div>
                    <a href="{{ route('notification.index') }}"
                        class="text-blue-600 hover:text-blue-700 font-semibold text-sm flex items-center gap-2">
                        Lihat Semua
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                </div>
                {{-- List --}}
                <div class="divide-y divide-slate-100 max-h-[650px] overflow-y-auto">
                    @forelse($notifikasi as $item)
                        @php
                            $pegawai = $item->employee;
                            // | Tanggal notifikasi
                            $tanggalNotifikasi = $item->tanggal_notifikasi
                                ? \Carbon\Carbon::parse($item->tanggal_notifikasi)->startOfDay()
                                : null;
                            $today = now()->startOfDay();
                            // | Keterangan waktu notifikasi
                            if (!$tanggalNotifikasi) {
                                $keteranganNotifikasi = 'Tanggal tidak tersedia';
                            } elseif ($tanggalNotifikasi->equalTo($today)) {
                                $keteranganNotifikasi = 'Hari Ini';
                            } elseif ($tanggalNotifikasi->lt($today)) {
                                $interval = $tanggalNotifikasi->diff($today);
                                $parts = [];
                                if ($interval->y > 0) {
                                    $parts[] = $interval->y . ' tahun';
                                }
                                if ($interval->m > 0) {
                                    $parts[] = $interval->m . ' bulan';
                                }
                                if ($interval->d > 0) {
                                    $parts[] = $interval->d . ' hari';
                                }
                                $keteranganNotifikasi = implode(' ', $parts) . ' yang lalu';
                            } else {
                                $keteranganNotifikasi = 'Akan datang';
                            }
                            // | Icon dan warna berdasarkan jenis notifikasi
                            switch ($item->jenis) {
                                case 'KGB':
                                    $icon = 'fa-money-bill-wave';
                                    $bg = 'bg-blue-100';
                                    $text = 'text-blue-600';
                                    $badge = 'bg-blue-100 text-blue-700';
                                    break;
                                case 'KENAIKAN_PANGKAT':
                                case 'Pangkat':
                                    $icon = 'fa-arrow-trend-up';
                                    $bg = 'bg-orange-100';
                                    $text = 'text-orange-600';
                                    $badge = 'bg-orange-100 text-orange-700';
                                    break;
                                case 'PENSIUN':
                                case 'Pensiun':
                                    $icon = 'fa-user-clock';
                                    $bg = 'bg-green-100';
                                    $text = 'text-green-600';
                                    $badge = 'bg-green-100 text-green-700';
                                    break;
                                default:
                                    $icon = 'fa-bell';
                                    $bg = 'bg-slate-100';
                                    $text = 'text-slate-600';
                                    $badge = 'bg-slate-100 text-slate-700';
                                    break;
                            }
                        @endphp
                        <div
                            class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 px-6 py-5 hover:bg-slate-50 transition">
                            {{-- LEFT --}}
                            <div class="flex gap-4 min-w-0">
                                {{-- Foto Pegawai --}}
                                <img src="{{ !$pegawai?->foto || $pegawai->foto === 'images/default-user.png'
                                    ? asset('images/default-user.png')
                                    : asset('storage/' . $pegawai->foto) }}"
                                    class="w-14 h-14 rounded-2xl object-cover flex-shrink-0 border border-slate-200"
                                    alt="Foto {{ $pegawai?->nama ?? 'Pegawai' }}">
                                {{-- Content --}}
                                <div class="min-w-0">
                                    {{-- Judul dan Jenis --}}
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="font-semibold text-slate-800">
                                            {{ $item->judul }}
                                        </h4>
                                        <span class="text-xs px-3 py-1 rounded-full font-medium {{ $badge }}">
                                            {{ str_replace('_', ' ', $item->jenis) }}
                                        </span>
                                    </div>
                                    {{-- Pesan Notifikasi --}}
                                    <p class="text-sm text-slate-600 mt-2">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($item->pesan), 80) }}
                                    </p>
                                    {{-- Informasi Pegawai --}}
                                    @if ($pegawai)
                                        <div
                                            class="flex flex-wrap items-center gap-x-5 gap-y-2 mt-3 text-sm text-slate-500">
                                            {{-- Nama Pegawai --}}
                                            <span class="flex items-center gap-2">
                                                <i class="fa-solid fa-user text-slate-400"></i>
                                                {{ $pegawai->nama }}
                                            </span>
                                            {{-- NIP --}}
                                            <span class="flex items-center gap-2">
                                                <i class="fa-solid fa-id-card text-slate-400"></i>
                                                {{ $pegawai->nip }}
                                            </span>
                                            {{-- Tanggal Notifikasi --}}
                                            <span class="flex items-center gap-2">
                                                <i class="fa-regular fa-calendar text-slate-400"></i>
                                                {{ $tanggalNotifikasi?->translatedFormat('d F Y') ?? 'Belum tersedia' }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            {{-- RIGHT --}}
                            <div class="text-right shrink-0">
                                {{-- Tanggal tidak tersedia --}}
                                @if (!$tanggalNotifikasi)
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-600 font-semibold">
                                        Tidak Tersedia
                                    </span>
                                    <p class="text-xs text-slate-500 mt-2">
                                        Tanggal notifikasi tidak tersedia
                                    </p>
                                    {{-- Notifikasi hari ini --}}
                                @elseif ($tanggalNotifikasi->equalTo($today))
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-700 font-semibold">
                                        Hari Ini
                                    </span>
                                    <p class="text-xs text-slate-500 mt-2">
                                        Notifikasi muncul hari ini
                                    </p>
                                    {{-- Notifikasi yang sudah berlalu --}}
                                @elseif ($tanggalNotifikasi->lt($today))
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 font-semibold">
                                        {{ $keteranganNotifikasi }}
                                    </span>
                                    <p class="text-xs text-slate-500 mt-2">
                                        Notifikasi {{ $tanggalNotifikasi->translatedFormat('d F Y') }}
                                    </p>
                                    {{-- Notifikasi yang akan datang --}}
                                @else
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-700 font-semibold">
                                        Akan Datang
                                    </span>
                                    <p class="text-xs text-slate-500 mt-2">
                                        Notifikasi {{ $tanggalNotifikasi->translatedFormat('d F Y') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @empty
                        {{-- Kondisi Kosong --}}
                        <div class="py-16 text-center">
                            <div class="mx-auto w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mb-5">
                                <i class="fa-regular fa-bell-slash text-4xl text-slate-400"></i>
                            </div>
                            <h4 class="text-lg font-semibold text-slate-700">
                                Tidak Ada Pengingat
                            </h4>
                            <p class="text-slate-500 mt-2">
                                Belum terdapat pengingat KGB, kenaikan pangkat, ataupun pensiun.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
            {{-- ================= AKTIVITAS ================= --}}
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 h-full">
                {{-- Header --}}
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">
                            Aktivitas Terbaru
                        </h3>
                        <p class="text-sm text-slate-500">
                            Riwayat aktivitas sistem
                        </p>
                    </div>
                    <a href="{{ route('activityLog.index') }}"
                        class="text-blue-600 hover:text-blue-700 text-sm font-semibold flex items-center gap-2">
                        Lihat Semua
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                </div>
                {{-- Timeline --}}
                <div class="flex-1 overflow-y-auto max-h-[650px] pr-2">
                    @forelse($aktivitas as $log)
                        <div class="relative pl-10 pb-8 last:pb-0">
                            {{-- Line --}}
                            <div class="absolute left-5 top-0 bottom-0 w-px bg-slate-200">
                            </div>
                            {{-- Dot --}}
                            <div
                                class="absolute left-3.5 top-3 w-4 h-4 rounded-full bg-blue-500 border-4 border-white shadow">
                            </div>
                            <div class="bg-slate-50 rounded-2xl p-4 hover:bg-blue-50 transition">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <img src="{{ !$foto || $foto === 'images/default-user.png' ? asset('images/default-user.png') : asset('storage/' . $foto) }}"
                                            class="w-12 h-12 rounded-full object-cover">
                                        <div>
                                            <h4 class="font-semibold text-slate-800">
                                                {{ $log->user?->employee?->nama ?? ($log->user?->username ?? 'System') }}
                                            </h4>
                                            <p class="text-sm text-slate-500 mt-1">
                                                {{ $log->aktivitas }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-left sm:text-right shrink-0">
                                        <div class="font-semibold text-slate-700">
                                            {{ $log->created_at->format('H:i') }}
                                        </div>
                                        <div class="text-xs text-slate-400">
                                            {{ $log->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-16 text-center">
                            <i class="fa-regular fa-clock text-5xl text-slate-300 mb-4">
                            </i>
                            <p class="text-slate-400">
                                Belum ada aktivitas.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
