@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    @php
        $user = auth()->user();
        $foto = $user->employee?->foto;
    @endphp
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-4 md:gap-6 mb-8">
            {{-- Total Pegawai --}}
            <a href="{{ route('employee.index') }}"
                class="block rounded-3xl border border-slate-200 bg-white p-6 shadow-sm
           transition duration-300 hover:-translate-y-1 hover:border-blue-200
           hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-blue-100">
                <div class="flex items-start justify-between gap-4">
                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-500">
                            Total Pegawai
                        </p>
                        <h2 class="mt-3 text-4xl font-bold leading-none text-slate-800">
                            {{ number_format($pegawaiAktif) }}
                        </h2>
                        <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-slate-500">
                            <span>
                                <span class="font-semibold text-green-600">
                                    {{ $pegawaiAktif }}
                                </span>
                                Aktif
                            </span>
                            <span class="text-slate-300">•</span>
                            <span>
                                <span class="font-semibold text-orange-500">
                                    {{ $pegawaiNonaktif }}
                                </span>
                                Nonaktif
                            </span>
                            <span class="text-slate-300">•</span>
                            <span>
                                <span class="font-semibold text-red-500">
                                    {{ $pegawaiPensiun }}
                                </span>
                                Pensiun
                            </span>
                        </div>
                    </div>
                    {{-- Icon --}}
                    <div
                        class="flex h-16 w-16 shrink-0 items-center justify-center
                   rounded-2xl bg-blue-100 text-blue-600">
                        <i class="fa-solid fa-users text-2xl"></i>
                    </div>
                </div>
            </a>
            {{-- Naik Pangkat --}}
            <a href="{{ route('card.index', ['jenis' => 'kp']) }}" class="block group">
                <div
                    class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100
                transition-all duration-200
                hover:-translate-y-1 hover:shadow-md cursor-pointer">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500 text-sm">
                                Naik Pangkat
                            </p>
                            <h2 class="text-4xl font-bold mt-3 text-slate-800">
                                {{ $naikPangkat }}
                            </h2>
                            <div class="mt-3 text-sm text-green-600 font-medium">
                                Dalam 2 bulan ke depan
                            </div>
                        </div>
                        <div
                            class="w-16 h-16 rounded-2xl bg-green-100 text-green-600
                        flex items-center justify-center
                        group-hover:scale-105 transition-transform">
                            <i class="fa-solid fa-arrow-trend-up text-2xl"></i>
                        </div>
                    </div>
                </div>
            </a>
            {{-- KGB --}}
            <a href="{{ route('card.index', ['jenis' => 'kgb']) }}" class="block group">
                <div
                    class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100
                transition-all duration-200
                hover:-translate-y-1 hover:shadow-md cursor-pointer">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500 text-sm">
                                KGB
                            </p>
                            <h2 class="text-4xl font-bold mt-3 text-slate-800">
                                {{ $kgb }}
                            </h2>
                            <div class="mt-3 text-sm text-purple-600 font-medium">
                                Dalam 2 bulan ke depan
                            </div>
                        </div>
                        <div
                            class="w-16 h-16 rounded-2xl bg-purple-100 text-purple-600
                        flex items-center justify-center
                        group-hover:scale-105 transition-transform">
                            <i class="fa-solid fa-money-bill-wave text-2xl"></i>
                        </div>
                    </div>
                </div>
            </a>
            {{-- Pensiun --}}
            <a href="{{ route('card.index', ['jenis' => 'mp']) }}" class="block group">
                <div
                    class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 transition-all duration-200hover:-translate-y-1 hover:shadow-md cursor-pointer">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-slate-500 text-sm">
                                Pensiun
                            </p>
                            <h2 class="text-4xl font-bold mt-3 text-slate-800">
                                {{ $pensiun }}
                            </h2>
                            <div class="mt-3 text-sm text-orange-600 font-medium">
                                Dalam 6 bulan ke depan
                            </div>
                        </div>
                        <div
                            class="w-16 h-16 rounded-2xl bg-orange-100 text-orange-600
                        flex items-center justify-center
                        group-hover:scale-105 transition-transform">
                            <i class="fa-solid fa-user-clock text-2xl"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <!-- MIDDLE -->
        <div class="space-y-6 mb-6">
            {{-- ===================== STATISTIK PEGAWAI ===================== --}}
            <div class="w-full bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                {{-- Header --}}
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-slate-800">
                        Statistik Pegawai
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Ringkasan statistik pegawai berdasarkan jenis, jenis kelamin, dan reminder
                        kepegawaian.
                    </p>
                </div>
                {{-- Statistik --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {{-- ================= Jenis Pegawai ================= --}}
                    <div class="bg-white border border-slate-100 rounded-2xl p-6 flex flex-col h-full">
                        <h4 class="text-center text-base font-semibold text-slate-700 mb-6">
                            Jenis Pegawai
                        </h4>
                        <div class="flex-1 flex items-center justify-center">
                            <div class="w-full max-w-xs xl:max-w-sm aspect-square">
                                <canvas id="chartJenisPegawai"></canvas>
                            </div>
                        </div>
                        <div id="legendJenisPegawai" class="mt-6 space-y-2"></div>
                    </div>
                    {{-- ================= Jenis Kelamin ================= --}}
                    <div class="bg-white border border-slate-100 rounded-2xl p-6 flex flex-col h-full">
                        <h4 class="text-center text-base font-semibold text-slate-700 mb-6">
                            Jenis Kelamin
                        </h4>
                        <div class="flex-1 flex items-center justify-center">
                            <div class="w-full max-w-xs xl:max-w-sm aspect-square">
                                <canvas id="ChartJenisKelamin"></canvas>
                            </div>
                        </div>
                        <div id="legendGender" class="mt-6 space-y-2"></div>
                    </div>
                    {{-- ================= Reminder ================= --}}
                    <div class="bg-white border border-slate-100 rounded-2xl p-6 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="text-base font-semibold text-slate-700">
                                Reminder
                            </h4>
                            <form method="GET">
                                <select name="reminder_year" onchange="this.form.submit()"
                                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm
                                   focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                    @foreach ($tahunList as $year)
                                        <option value="{{ $year }}"
                                            {{ $tahunReminder == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                        <div class="flex-1 flex items-center justify-center">
                            <div class="w-full max-w-xs xl:max-w-sm aspect-square">
                                <canvas id="pegawaiChart"></canvas>
                            </div>
                        </div>
                        <div class="text-center text-xs text-slate-500 mt-6">
                            <span class="font-semibold text-slate-700" id="pegawaiReminderCount">
                                {{ $chartReminder['pegawai_reminder'] ?? 0 }}
                            </span>
                            Pegawai
                            <span class="mx-2">•</span>
                            <span class="font-semibold text-slate-700" id="eventReminderCount">
                                {{ $chartReminder['total_event'] ?? 0 }}
                            </span>
                            Event
                        </div>
                        <div id="legendReminder" class="mt-6 space-y-2"></div>
                    </div>
                </div>
            </div>
            {{-- ===================== BOTTOM SECTION ===================== --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Pengingat --}}
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
                                /*
                                |--------------------------------------------------------------------------
                                | Tanggal notifikasi
                                |--------------------------------------------------------------------------
                                */
                                $tanggalNotifikasi = $item->tanggal_notifikasi ? \Carbon\Carbon::parse($item->tanggal_notifikasi)->startOfDay() : null;
                                $today = now()->startOfDay();
                                /*
                                |--------------------------------------------------------------------------
                                | Selisih hari sejak notifikasi muncul
                                |--------------------------------------------------------------------------
                                */
                                $hariNotifikasi = $tanggalNotifikasi ? $tanggalNotifikasi->diffInDays($today, false) : null;
                                /*
                                |--------------------------------------------------------------------------
                                | Keterangan umur notifikasi
                                |--------------------------------------------------------------------------
                                */
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
                                /*
                                |--------------------------------------------------------------------------
                                | Icon dan warna berdasarkan jenis
                                |--------------------------------------------------------------------------
                                */
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
                                }
                            @endphp
                            <div
                                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 px-6 py-5 hover:bg-slate-50 transition">
                                {{-- LEFT --}}
                                <div class="flex gap-4">
                                    {{-- foto --}}
                                    <img src="{{ !$pegawai?->foto || $pegawai->foto === 'images/default-user.png'
                                        ? asset('images/default-user.png')
                                        : asset('storage/' . $pegawai->foto) }}"
                                        class="w-14 h-14 rounded-2xl object-cover flex-shrink-0 border border-slate-200"
                                        alt="Foto {{ $pegawai?->nama ?? 'Pegawai' }}">
                                    {{-- Content --}}
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="font-semibold text-slate-800">
                                                {{ $item->judul }}
                                            </h4>
                                            <span class="text-xs px-3 py-1 rounded-full font-medium {{ $badge }}">
                                                {{ $item->jenis }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-slate-600 mt-2">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($item->pesan), 80) }}
                                        </p>
                                        @if ($pegawai)
                                            <div class="flex flex-wrap items-center gap-5 mt-3 text-sm text-slate-500">
                                                <span class="flex items-center gap-2">
                                                    <i class="fa-solid fa-user text-slate-400"></i>
                                                    {{ $pegawai->nama }}
                                                </span>
                                                <span class="flex items-center gap-2">
                                                    <i class="fa-solid fa-id-card text-slate-400"></i>
                                                    {{ $pegawai->nip }}
                                                </span>
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
                                    @if (!$tanggalNotifikasi)
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-600 font-semibold">
                                            Tidak tersedia
                                        </span>
                                        <p class="text-xs text-slate-500 mt-2">
                                            Tanggal notifikasi tidak tersedia
                                        </p>
                                    @elseif ($tanggalNotifikasi->equalTo($today))
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-700 font-semibold">
                                            Hari Ini
                                        </span>
                                        <p class="text-xs text-slate-500 mt-2">
                                            Notifikasi muncul hari ini
                                        </p>
                                    @elseif ($tanggalNotifikasi->lt($today))
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 font-semibold">
                                            {{ $keteranganNotifikasi }}
                                        </span>
                                        <p class="text-xs text-slate-500 mt-2">
                                            Notifikasi {{ $tanggalNotifikasi->translatedFormat('d F Y') }}
                                        </p>
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
                            <div class="py-16 text-center">
                                <div
                                    class="mx-auto w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mb-5">
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
                {{-- Aktivitas --}}
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 h-full">
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
                                                class="w-12 h-12 rounded-full object-cover shadow">
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
    </div>
@endsection
