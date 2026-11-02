@extends('layouts.app')
@section('content')
    <div class="w-full p-4 md:p-6">
        {{-- HEADER --}}
        <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    {{ $user->role === 'super_admin' ? 'Riwayat Aktivitas Seluruh Sistem' : 'Riwayat Aktivitas Unit Kerja' }}
                </h1>
                <p class="text-slate-500">
                    {{ $user->role === 'super_admin'
                        ? 'Seluruh aktivitas pengguna pada sistem kepegawaian dari semua unit kerja.'
                        : 'Riwayat aktivitas pengguna yang tercatat pada unit kerja Anda.' }}
                </p>
            </div>
        </div>
        {{-- STATISTIK --}}
        <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-3">
            <div class="p-5 text-white shadow-sm bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl">
                <p class="text-sm opacity-90">Total Aktivitas</p>
                <h3 class="mt-2 text-3xl font-bold">
                    {{ number_format($totalAktivitas) }}
                </h3>
            </div>
            <div class="p-5 text-white shadow-sm bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl">
                <p class="text-sm opacity-90">Pengguna Aktif Hari Ini</p>
                <h3 class="mt-2 text-3xl font-bold">
                    {{ number_format($penggunaAktifHariIni) }}
                </h3>
            </div>
            <div class="p-5 text-white shadow-sm bg-gradient-to-r from-red-500 to-rose-600 rounded-2xl">
                <p class="text-sm opacity-90">Aktivitas Hari Ini</p>
                <h3 class="mt-2 text-3xl font-bold">
                    {{ number_format($aktivitasHariIni) }}
                </h3>
            </div>
        </div>
        {{-- FILTER --}}
        <form method="GET" action="{{ route('activityLog.index') }}"
            class="p-5 mb-6 bg-white border border-slate-200 rounded-3xl">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                {{-- PENCARIAN --}}
                <div>
                    <label for="search" class="block mb-2 text-sm font-medium text-slate-700">
                        Cari Aktivitas
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="text-slate-400 fa-solid fa-magnifying-glass"></i>
                        </div>
                        <input id="search" type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Cari aktivitas, deskripsi, atau pengguna..."
                            class="block w-full p-3 pl-10 text-sm border bg-slate-50 border-slate-300 text-slate-900 rounded-xl focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                {{-- JENIS AKTIVITAS --}}
                <div>
                    <label for="aktivitas" class="block mb-2 text-sm font-medium text-slate-700">
                        Jenis Aktivitas
                    </label>
                    <select id="aktivitas" name="aktivitas"
                        class="block w-full p-3 text-sm border bg-slate-50 border-slate-300 text-slate-900 rounded-xl focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Aktivitas</option>
                        @foreach ($activities as $activity)
                            <option value="{{ $activity }}" @selected(($filters['aktivitas'] ?? '') === $activity)>
                                {{ $activity }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- TANGGAL --}}
                <div>
                    <label for="tanggal" class="block mb-2 text-sm font-medium text-slate-700">
                        Tanggal
                    </label>
                    <input id="tanggal" type="date" name="tanggal" value="{{ $filters['tanggal'] ?? '' }}"
                        class="block w-full p-3 text-sm border bg-slate-50 border-slate-300 text-slate-900 rounded-xl focus:ring-blue-500 focus:border-blue-500">
                </div>
                {{-- TOMBOL FILTER --}}
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="flex-1 px-5 py-3 font-medium text-white transition bg-blue-600 hover:bg-blue-700 rounded-xl">
                        <i class="mr-2 fa-solid fa-filter"></i>
                        Filter
                    </button>
                    <a href="{{ route('activityLog.index') }}" title="Reset filter" aria-label="Reset filter"
                        class="px-5 py-3 transition border border-slate-300 hover:bg-slate-50 rounded-xl">
                        <i class="fa-solid fa-rotate-right"></i>
                    </a>
                </div>
            </div>
        </form>
        {{-- TIMELINE AKTIVITAS --}}
        <x-activity-card :logs="$data" :show-pagination="true" />
    </div>
@endsection
