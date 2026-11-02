@extends('layouts.app')
@section('content')
    @php
        $isUser = auth()->user()->role === 'user';
        $indexUrl = $isUser ? route('report.kgb.user') : route('report.kgb');
        $printUrl = $isUser
            ? route('report.kgb.user.print', ['nip' => $employee->nip])
            : route('report.print', ['id' => $employee->nip]);
    @endphp
    <div class="bg-slate-100 min-h-screen py-8">
        {{-- Toolbar --}}
        <div class="max-w-6xl mx-auto mb-6 relative flex justify-end items-center min-h-12">
            {{-- Judul di tengah --}}
            <h1 class="absolute left-1/2 -translate-x-1/2 text-2xl font-bold text-slate-800 text-center whitespace-nowrap">
                Preview Surat Kenaikan Gaji Berkala
            </h1>
            {{-- Tombol di kanan --}}
            <div class="flex items-center gap-3">
                <a href="{{ auth()->user()->role === 'user' ? route('report.kgb.user') : route('report.kgb') }}"
                    class="px-4 py-2 rounded-xl bg-slate-600 text-white hover:bg-slate-700 transition">
                    <i class="fa fa-arrow-left mr-2"></i>
                    Kembali
                </a>
                <form id="kgb-print-form" method="POST" action="{{ $printUrl }}" data-index-url="{{ $indexUrl }}">
                    @csrf
                    {{-- IDENTITAS PEGAWAI --}} <input type="hidden" id="employee-id" value="{{ $employee->nip }}">
                    <input type="hidden" id="history-id" value="{{ $history->id ?? '' }}">
                    <input type="hidden" name="nip" value="{{ $employee->nip }}">
                    {{-- SK LAMA --}}
                    <input type="hidden" id="nomor_sk_lama" name="nomor_sk_lama">
                    <input type="hidden" id="tanggal_sk_lama" name="tanggal_sk_lama">
                    {{-- SK BARU --}}
                    <input type="hidden" id="nomor_sk" name="nomor_sk_baru">
                    <input type="hidden" id="tanggal_sk" name="tanggal_sk_baru">
                    {{-- PEJABAT --}}
                    <input type="hidden" id="pejabat" name="pejabat">
                    {{-- MASA KERJA LAMA --}}
                    <input type="hidden" id="masa_kerja_tahun_lama" name="tahun_masa_kerja_lama">
                    <input type="hidden" id="masa_kerja_bulan_lama" name="bulan_masa_kerja_lama">
                    {{-- MASA KERJA BARU --}}
                    <input type="hidden" id="masa_kerja_tahun_baru" name="tahun_masa_kerja_baru">
                    <input type="hidden" id="masa_kerja_bulan_baru" name="bulan_masa_kerja_baru">
                    {{-- GAJI --}}
                    <input type="hidden" id="gaji_lama" name="gaji_pokok_lama">
                    <input type="hidden" id="gaji_baru" name="gaji_pokok_baru">
                    {{-- TMT KGB --}}
                    <input type="hidden" name="tmt_kgb_lama" value="{{ $surat->tmt_kgb_lama?->toDateString() }}">
                    <input type="hidden" name="tmt_kgb_baru" value="{{ $surat->tmt_kgb_baru?->toDateString() }}">
                    {{-- DASAR HUKUM --}}
                    <input type="hidden" id="dasar_hukum_kgb" name="dasar_hukum_kgb">
                    {{-- TOMBOL CETAK --}}
                    <button type="button" id="kgb-print-button" onclick="openKgbPrintConfirmation()"
                        class="px-4 py-2 rounded-xl bg-green-600 text-white hover:bg-green-700 transition">
                        <i class="fa fa-print mr-2"></i>
                        Cetak
                    </button>
                </form>
            </div>
        </div>
        {{-- Preview Surat --}}
        @include('report.surat')
    </div>
    {{-- ============================================================
         MODAL KONFIRMASI CETAK
    ============================================================ --}}
    <div id="print-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="print-confirm-title"
        class="modal fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div
            class="bg-white w-full max-w-md rounded-3xl shadow-[0_20px_60px_rgba(0,0,0,0.15)] overflow-hidden animate-[modalShow_.25s_ease]">
            {{-- HEADER --}}
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-5 relative">
                <div class="absolute right-0 top-0 opacity-10">
                    <i class="fa-solid fa-print text-[120px]"></i>
                </div>
                <div class="relative flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-white text-xl">
                        <i class="fa-solid fa-print"></i>
                    </div>
                    <div>
                        <h2 id="print-confirm-title" class="text-xl font-bold text-white">
                            Konfirmasi Cetak KGB
                        </h2>
                        <p class="text-green-100 text-sm">
                            Konfirmasi pencetakan surat
                        </p>
                    </div>
                </div>
            </div>
            {{-- BODY --}}
            <div class="p-6">
                <div
                    class="w-20 h-20 mx-auto rounded-full bg-green-100 flex items-center justify-center text-green-600 text-3xl mb-5">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <h3 class="text-center text-lg font-bold text-slate-800 mb-2">
                    Yakin ingin mencetak surat KGB?
                </h3>
                <p class="text-center text-slate-500">
                    Surat Kenaikan Gaji Berkala akan diproses dan
                    halaman cetak akan dibuka pada tab baru.
                </p>
            </div>
            {{-- FOOTER --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <button type="button" onclick="closeKgbPrintConfirmation()"
                    class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-white transition">
                    <i class="fa-solid fa-xmark mr-2"></i>
                    Batal
                </button>
                <button type="button" onclick="confirmKgbPrint()"
                    class="px-5 py-2.5 rounded-xl bg-green-600 text-white hover:bg-green-700 shadow-lg shadow-green-200 transition">
                    <i class="fa-solid fa-print mr-2"></i>
                    Ya, Cetak
                </button>
            </div>
        </div>
    </div>
@endsection
