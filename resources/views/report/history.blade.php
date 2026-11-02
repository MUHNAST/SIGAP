@extends('layouts.app')
@section('content')
    @php
        $user = auth()->user();

        $view = $user->hasPermission('riwayat_kgb.manage');
    @endphp
    <div class="p-6">
        @if ($view)
            {{-- HEADER --}}
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800">
                        Riwayat Kenaikan Gaji Berkala
                    </h1>
                    <p class="text-slate-500 mt-1">
                        Menampilkan seluruh riwayat Kenaikan Gaji Berkala yang telah diterbitkan.
                    </p>
                </div>
            </div>
            {{-- SUMMARY --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-8">
                    {{-- Header --}}
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl bg-emerald-100 flex items-center justify-center">
                                <i class="fa-solid fa-clock-rotate-left text-3xl text-emerald-600"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-slate-800">
                                    Riwayat KGB
                                </h2>
                                <p class="text-slate-500 mt-1">
                                    Seluruh Surat Kenaikan Gaji Berkala yang telah diproses dan dicetak.
                                </p>
                            </div>
                        </div>
                        <span
                            class="inline-flex items-center px-5 py-2 rounded-full bg-emerald-100 text-emerald-700 font-semibold">
                            {{ $jumlahHistory ?? 0 }} Riwayat
                        </span>
                    </div>
                    {{-- Statistik --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- Total Riwayat --}}
                        <div class="rounded-2xl bg-slate-50 p-5 border border-slate-100">
                            <p class="text-sm text-slate-500">
                                Total Riwayat
                            </p>
                            <h3 class="text-3xl font-bold text-slate-800 mt-2">
                                {{ number_format($jumlahHistory ?? 0) }}
                            </h3>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-5 border border-slate-100">
                            <p class="text-sm text-slate-500">Periode Riwayat</p>

                            <h3 class="text-3xl font-bold text-slate-800 mt-2">
                                {{ $periodeAwal ? $periodeAwal . ' - ' . $periodeAkhir : '-' }}
                            </h3>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-5 border border-slate-100">
                            <p class="text-sm text-slate-500">Cetakan Terakhir</p>

                            <h3 class="text-lg font-bold text-slate-800 mt-2">
                                {{ $cetakanTerakhir?->translatedFormat('d F Y') ?? '-' }}
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            {{-- TABLE --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm mt-6 overflow-hidden">
                <div class="overflow-x-auto p-5">
                    <table id="history-table" class="js-datatable min-w-full table-auto text-sm text-left text-gray-700">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600">
                                <th class="px-6 py-4 w-20">No</th>
                                <th class="px-6 py-4">NIP</th>
                                <th class="px-6 py-4">Nama Pegawai</th>
                                <th class="px-6 py-4">Nomor SK Baru</th>
                                <th class="px-6 py-4">Tanggal SK Baru</th>
                                <th class="px-6 py-4">TMT KGB Baru</th>
                                <th class="px-6 py-4">Gaji Lama</th>
                                <th class="px-6 py-4">Gaji Baru</th>
                                @if ($view)
                                    <th class="px-6 py-4 text-center">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($histories as $history)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ format_nip($history->nip) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $history->employee->nama ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $history->nomor_sk_baru ?: '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ optional($history->tanggal_sk_baru)->translatedFormat('d F Y') ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-blue-600 font-semibold">
                                        {{ optional($history->tmt_kgb_baru)->translatedFormat('d F Y') ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ format_rupiah($history->gaji_pokok_lama) }}
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-green-600">
                                        {{ format_rupiah($history->gaji_pokok_baru) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center gap-2">
                                            {{-- Preview ulang --}}
                                            @if ($view)
                                                <a href="{{ route('history.preview', $history->id) }}"
                                                    class="w-9 h-9 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-600 flex items-center justify-center">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            @endif
                                            {{-- Cetak ulang --}}
                                            {{-- <a href="{{ route('history.print', $history->id) }}" target="_blank"
                                                class="w-9 h-9 rounded-xl bg-green-50 hover:bg-green-100 text-green-600 flex items-center justify-center">
                                                <i class="fa-solid fa-print"></i>
                                            </a> --}}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-16 text-slate-400">
                                        <i class="fa-solid fa-folder-open text-4xl mb-3 block"></i>
                                        Belum ada riwayat KGB.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white rounded-3xl p-16 text-center">
                <i class="fa-solid fa-folder-open text-5xl text-slate-300 mb-4"></i>
                <h3 class="text-lg font-semibold text-slate-700">
                    Data Riwayat KGB belum tersedia.
                </h3>
            </div>
        @endif
    </div>
@endsection
