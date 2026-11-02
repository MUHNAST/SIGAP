@extends('layouts.app')
@section('content')
    <div class="w-full p-4 md:p-6">
        {{-- HEADER --}}
        <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    {{ $title }}
                </h1>
                <p class="mt-1 text-slate-500">
                    Lihat dan telusuri riwayat aktivitas akun Anda
                    pada sistem kepegawaian.
                </p>
            </div>
        </div>
        {{-- INFORMASI PENGGUNA --}}
        <div class="p-5 mb-6 bg-white border border-slate-200 rounded-3xl">
            <div class="flex items-center gap-4">
                <div
                    class="flex items-center justify-center w-14 h-14
                    text-xl text-blue-600 bg-blue-100 rounded-2xl">
                    <i class="fa-solid fa-user-clock"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-800">
                        {{ $user->employee?->nama ?? ($user->nama ?? 'Pengguna') }}
                    </h2>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-sm text-slate-500">
                        @if ($user->employee?->nip)
                            <span>
                                NIP: {{ $user->employee->nip }}
                            </span>
                        @endif
                        <span>
                            Role: {{ str($user->role)->replace('_', ' ')->title() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        {{-- FILTER --}}
        <form method="GET" action="{{ route('activityLog.user') }}"
            class="p-5 mb-6 bg-white border border-slate-200 rounded-3xl">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
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
                            placeholder="Cari aktivitas atau deskripsi..."
                            class="block w-full p-3 pl-10 text-sm border bg-slate-50
                                border-slate-300 text-slate-900 rounded-xl
                                focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                {{-- TANGGAL --}}
                <div>
                    <label for="tanggal" class="block mb-2 text-sm font-medium text-slate-700">
                        Tanggal
                    </label>
                    <input id="tanggal" type="date" name="tanggal" value="{{ $filters['tanggal'] ?? '' }}"
                        class="block w-full p-3 text-sm border bg-slate-50
                            border-slate-300 text-slate-900 rounded-xl
                            focus:ring-blue-500 focus:border-blue-500">
                </div>
                {{-- TOMBOL --}}
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="flex-1 px-5 py-3 font-medium text-white
                            transition bg-blue-600 hover:bg-blue-700 rounded-xl">
                        <i class="mr-2 fa-solid fa-filter"></i>
                        Filter
                    </button>
                    <a href="{{ route('activityLog.user') }}" title="Reset filter" aria-label="Reset filter"
                        class="px-5 py-3 transition border border-slate-300
                            hover:bg-slate-50 rounded-xl">
                        <i class="fa-solid fa-rotate-right"></i>
                    </a>
                </div>
            </div>
        </form>
        {{-- RIWAYAT AKTIVITAS --}}
        <x-activity-card :logs="$data" :show-pagination="true" />
    </div>
@endsection
