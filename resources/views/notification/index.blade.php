@extends('layouts.app')
@section('content')
    <div class="w-full p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    Semua Notifikasi
                </h1>
                <p class="text-slate-500">
                    Daftar seluruh notifikasi sistem kepegawaian.
                </p>
            </div>
            @if ($statistics['belumDibaca'] > 0)
                <button type="button" data-modal-target="confirmReadAllModal" data-modal-toggle="confirmReadAllModal"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl shadow-sm transition">
                    <i class="fa-solid fa-check-double mr-2"></i>
                    Tandai Semua Dibaca
                </button>
            @endif
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-2xl p-5 shadow-sm">
                <p class="text-sm opacity-90">
                    Total Notifikasi
                </p>
                <h3 class="text-3xl font-bold mt-2">
                    {{ number_format($statistics['total']) }}
                </h3>
            </div>
            <div class="bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-2xl p-5 shadow-sm">
                <p class="text-sm opacity-90">
                    Belum Dibaca
                </p>
                <h3 class="text-3xl font-bold mt-2">
                    {{ number_format($statistics['belumDibaca']) }}
                </h3>
            </div>
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-2xl p-5 shadow-sm">
                <p class="text-sm opacity-90">
                    Sudah Dibaca
                </p>
                <h3 class="text-3xl font-bold mt-2">
                    {{ number_format($statistics['total'] - $statistics['belumDibaca']) }}
                </h3>
            </div>
        </div>
        <!-- Filter -->
        <form method="GET" class="bg-white border border-slate-200 rounded-3xl p-5 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Cari -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-slate-700">
                        Cari Notifikasi
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" id="searchNotification"
                            placeholder="Cari judul atau isi notifikasi..."
                            class="bg-slate-50 border border-slate-300 rounded-xl pl-10 p-3 w-full focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <!-- Jenis -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-slate-700">
                        Jenis Notifikasi
                    </label>
                    <select name="kategori" id="kategoriNotification"
                        class="bg-slate-50 border border-slate-300 rounded-xl p-3 w-full focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Jenis</option>
                        <option value="Pangkat" @selected(request('kategori') == 'Pangkat')>
                            Kenaikan Pangkat
                        </option>
                        <option value="KGB" @selected(request('kategori') == 'KGB')>
                            Kenaikan Gaji Berkala
                        </option>
                        <option value="KGB Diproses" @selected(request('kategori') == 'KGB Diproses')>
                            Kenaikan Gaji Berkala Telah Diproses
                        </option>
                        <option value="Pensiun" @selected(request('kategori') == 'Pensiun')>
                            Masa Pensiun
                        </option>
                    </select>
                </div>
                <!-- Status -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-slate-700">
                        Status
                    </label>
                    <select name="sudah_dibaca"
                        class="bg-slate-50 border border-slate-300 rounded-xl p-3 w-full focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Status</option>
                        <option value="0" @selected(request('sudah_dibaca') === '0')>
                            Belum Dibaca
                        </option>
                        <option value="1" @selected(request('sudah_dibaca') === '1')>
                            Sudah Dibaca
                        </option>
                    </select>
                </div>
                <!-- Tombol -->
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-5 py-3 transition">
                        <i class="fa-solid fa-filter mr-2"></i>
                        Filter
                    </button>
                    <a href="{{ route('notification.index') }}"
                        class="px-5 py-3 rounded-xl border border-slate-300 hover:bg-slate-50 transition">
                        <i class="fa-solid fa-rotate-right"></i>
                    </a>
                </div>
            </div>
        </form>
        <!-- Daftar Notifikasi -->
        <div class="space-y-5">
            @forelse ($data as $notification)
                @php
                    $isUnread = !$notification->sudah_dibaca;
                    $cardClass = $isUnread
                        ? 'bg-blue-50 border-blue-200 border-l-4 border-l-blue-600'
                        : 'bg-white border-slate-200';
                    $badgeClass = $isUnread ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
                    $status = $isUnread ? 'Belum Dibaca' : 'Dibaca';
                @endphp
                <div
                    class="bg-white border rounded-3xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden {{ $cardClass }}">
                    <div class="p-6">
                        <div class="flex flex-col lg:flex-row lg:justify-between gap-6">
                            <!-- KIRI -->
                            <div class="flex gap-5 flex-1">
                                <!-- ICON -->
                                <div
                                    class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0 {{ $notification->icon['bg'] }}">
                                    <i
                                        class="fa-solid {{ $notification->icon['icon'] }} text-xl {{ $notification->icon['text'] }}">
                                    </i>
                                </div>
                                <!-- ISI -->
                                <div class="flex-1 min-w-0">
                                    <!-- Judul -->
                                    <h3 class="text-lg font-bold text-slate-800">
                                        {{ $notification->judul }}
                                    </h3>
                                    <!-- Jenis -->
                                    <div class="mt-2">
                                        <span
                                            class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium">
                                            <i class="fa-solid fa-tag"></i>
                                            {{ ucfirst($notification->jenis) }}
                                        </span>
                                    </div>
                                    <!-- Pesan -->
                                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">

                                        <div class="whitespace-pre-line text-slate-700 leading-7">
                                            {{ $notification->pesan }}
                                        </div>

                                    </div>
                                    <!-- Info -->
                                    <div class="flex flex-wrap gap-5 mt-5 text-sm text-slate-500">
                                        @if ($notification->employee)
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-user text-blue-500"></i>
                                                <span>
                                                    {{ $notification->employee->nama }}
                                                </span>
                                            </div>
                                        @endif
                                        <div class="flex items-center gap-2">
                                            <i class="fa-regular fa-calendar text-emerald-500"></i>
                                            <span>
                                                {{ $notification->created_at->translatedFormat('d F Y') }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fa-regular fa-clock text-amber-500"></i>
                                            <span>
                                                {{ $notification->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- KANAN -->
                            <div class="flex lg:flex-col items-center lg:items-end justify-between gap-4">
                                <span
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold {{ $badgeClass }}">
                                    <i class="fa-solid {{ $isUnread ? 'fa-envelope' : 'fa-envelope-open' }}">
                                    </i>
                                    {{ $status }}
                                </span>
                                @if (!$notification->sudah_dibaca)
                                    <form action="{{ route('notification.read', $notification->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit"
                                            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl transition">
                                            <i class="fa-solid fa-check"></i>
                                            Tandai Dibaca
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white border border-slate-200 rounded-3xl p-14 text-center">
                    <div class="mx-auto w-24 h-24 rounded-full bg-slate-100 flex items-center justify-center mb-6">
                        <i class="fa-solid fa-bell-slash text-4xl text-slate-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-700">
                        Belum Ada Notifikasi
                    </h3>
                    <p class="text-slate-500 mt-3 max-w-md mx-auto">
                        Semua pemberitahuan mengenai kenaikan pangkat, KGB, pensiun,
                        maupun informasi sistem akan ditampilkan di halaman ini.
                    </p>
                </div>
            @endforelse
        </div>
        @if ($data->hasPages())
            <div class="mt-6">
                {{ $data->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
    @include('notification.confirmation')
@endsection
