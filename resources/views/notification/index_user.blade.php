@extends('layouts.app')
@section('content')
    <div class="w-full p-4 md:p-6">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">
                Notifikasi Saya
            </h1>
            <p class="text-slate-500 mt-1">
                Informasi jadwal kepegawaian Anda.
            </p>
        </div>
        {{-- Statistik --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-slate-500">Total Notifikasi</p>
                        <h3 class="text-3xl font-bold text-slate-800 mt-2">
                            {{ number_format($statistics['total']) }}
                        </h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
                        <i class="fa-solid fa-bell text-xl text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-slate-500">Belum Dibaca</p>
                        <h3 class="text-3xl font-bold text-red-600 mt-2">
                            {{ number_format($statistics['belumDibaca']) }}
                        </h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                        <i class="fa-solid fa-envelope text-xl text-red-600"></i>
                    </div>
                </div>
            </div>
        </div>
        {{-- Filter --}}
        <form method="GET" class="bg-white border border-slate-200 rounded-2xl p-5 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                {{-- Cari --}}
                <div>
                    <label class="block mb-2 text-sm font-medium text-slate-700">
                        Cari Notifikasi
                    </label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari judul atau isi notifikasi..."
                        class="bg-slate-50 border border-slate-300 rounded-xl p-3 w-full">
                </div>
                {{-- Jenis --}}
                <div>
                    <label class="block mb-2 text-sm font-medium text-slate-700">
                        Jenis Notifikasi
                    </label>
                    <select name="kategori" class="bg-slate-50 border border-slate-300 rounded-xl p-3 w-full">
                        <option value="">Semua Jenis</option>
                        <option value="Pangkat" @selected(request('kategori') == 'Pangkat')>
                            Kenaikan Pangkat
                        </option>
                        <option value="KGB" @selected(request('kategori') == 'KGB')>
                            Kenaikan Gaji Berkala
                        </option>
                        <option value="KGB SELESAI" @selected(request('kategori') == 'KGB SELESAI')>
                            KGB Telah Diproses
                        </option>
                        <option value="Pensiun" @selected(request('kategori') == 'Pensiun')>
                            Masa Pensiun
                        </option>
                    </select>
                </div>
                {{-- Status --}}
                <div>
                    <label class="block mb-2 text-sm font-medium text-slate-700">
                        Status
                    </label>
                    <select name="sudah_dibaca" class="bg-slate-50 border border-slate-300 rounded-xl p-3 w-full">
                        <option value="">Semua Status</option>
                        <option value="0" @selected(request('sudah_dibaca') === '0')>
                            Belum Dibaca
                        </option>
                        <option value="1" @selected(request('sudah_dibaca') === '1')>
                            Sudah Dibaca
                        </option>
                    </select>
                </div>
                {{-- Tombol --}}
                <div class="flex items-end gap-2">
                    <a href="{{ route('notification.index') }}"
                        class="flex-1 inline-flex justify-center items-center gap-2 whitespace-nowrap px-4 py-3 rounded-xl border border-slate-300 hover:bg-slate-50 transition">
                        <i class="fa-solid fa-rotate-right"></i>
                        Reset
                    </a>
                    <button type="submit"
                        class="flex-1 inline-flex justify-center items-center gap-2 whitespace-nowrap bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-4 py-3 transition">
                        <i class="fa-solid fa-filter"></i>
                        Filter
                    </button>
                </div>
            </div>
        </form>
        {{-- Daftar Notifikasi --}}
        <div class="space-y-4">
            @forelse ($data as $notification)
                @php
                    $isUnread = !$notification->sudah_dibaca;
                    $cardClass = $isUnread
                        ? 'bg-white border-blue-200 border-l-4 border-l-blue-600'
                        : 'bg-white border-slate-200';
                    $badgeClass = $isUnread ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700';
                @endphp
                <div class="border rounded-2xl shadow-sm overflow-hidden {{ $cardClass }}">
                    <div class="p-5">
                        <div class="flex flex-col sm:flex-row gap-4">
                            {{-- Icon --}}
                            <div
                                class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 {{ $notification->icon['bg'] }}">
                                <i
                                    class="fa-solid {{ $notification->icon['icon'] }} text-xl {{ $notification->icon['text'] }}"></i>
                            </div>
                            {{-- Isi --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap justify-between items-center gap-3">
                                    <h3 class="text-lg font-bold text-slate-800">
                                        {{ $notification->judul }}
                                    </h3>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                        {{ $isUnread ? 'Belum Dibaca' : 'Sudah Dibaca' }}
                                    </span>
                                </div>
                                <span
                                    class="inline-flex mt-2 items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium">
                                    <i class="fa-solid fa-tag"></i>
                                    {{ ucfirst($notification->jenis) }}
                                </span>
                                <div class="mt-4 rounded-xl border border-slate-200 bg-white/80 p-4">
                                    <p class="text-slate-700 leading-7 whitespace-pre-line">
                                        {{ $notification->pesan }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-4 mt-4 text-sm text-slate-500">
                                    <span>
                                        <i class="fa-regular fa-calendar text-blue-500 mr-1"></i>
                                        {{ $notification->created_at->translatedFormat('d F Y') }}
                                    </span>
                                    <span>
                                        <i class="fa-regular fa-clock text-amber-500 mr-1"></i>
                                        {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                @if ($isUnread)
                                    <form action="{{ route('notification.read', $notification->id) }}" method="POST"
                                        class="mt-4">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit"
                                            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl">
                                            <i class="fa-solid fa-check"></i>
                                            Tandai Sudah Dibaca
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center">
                    <div class="mx-auto w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mb-5">
                        <i class="fa-solid fa-bell-slash text-3xl text-slate-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-700">
                        Belum Ada Notifikasi
                    </h3>
                    <p class="text-slate-500 mt-2 max-w-md mx-auto">
                        Informasi kenaikan pangkat, kenaikan gaji berkala,
                        dan masa pensiun Anda akan ditampilkan di halaman ini.
                    </p>
                </div>
            @endforelse
        </div>
        {{-- Pagination --}}
        @if ($data->hasPages())
            <div class="mt-6">
                {{ $data->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection
