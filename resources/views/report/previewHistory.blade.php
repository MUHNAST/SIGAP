@extends('layouts.app')
@section('content')
    <div class="bg-slate-100 min-h-screen py-8">
        <div class="max-w-6xl mx-auto mb-6 relative flex justify-end items-center">
            <h1 class="absolute left-1/2 -translate-x-1/2 text-2xl font-bold text-slate-800 text-center whitespace-nowrap">
                Preview Surat Kenaikan Gaji Berkala
            </h1>
            <div class="flex gap-2">
                <a href="{{ route('history.kgb') }}" class="px-4 py-2 rounded-xl bg-slate-600 text-white">
                    Kembali
                </a>
                <button type="button" id="btn-print"
                    class="px-4 py-2 rounded-xl bg-green-600 text-white hover:bg-green-700 transition">
                    <i class="fa fa-print mr-2"></i>
                    Cetak
                </button>
            </div>
        </div>
        <div id="surat-preview">
            @include('report.suratHistory')
        </div>
    </div>
    <input type="hidden" id="employee-id" value="{{ $employee->nip }}">
    <input type="hidden" id="history-id" value="{{ $history->id }}">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const historyId = @json($history->id);
            /*
            |--------------------------------------------------------------------------
            | Storage khusus untuk history ini
            |--------------------------------------------------------------------------
            */
            const storageKey = `surat-history-${historyId}`;
            const editableElements = document.querySelectorAll(
                '#surat-preview [contenteditable="true"][data-field]'
            );
            /*
            |--------------------------------------------------------------------------
            | Ambil data edit sementara jika sebelumnya sudah ada
            |--------------------------------------------------------------------------
            */
            function loadTemporaryData() {
                const savedData = sessionStorage.getItem(storageKey);
                if (!savedData) {
                    return;
                }
                try {
                    const data = JSON.parse(savedData);
                    editableElements.forEach(element => {
                        const field = element.dataset.field;
                        if (
                            Object.prototype.hasOwnProperty.call(
                                data,
                                field
                            )
                        ) {
                            element.innerHTML = data[field];
                        }
                    });
                } catch (error) {
                    console.error(
                        'Gagal membaca data edit sementara:',
                        error
                    );
                }
            }
            /*
            |--------------------------------------------------------------------------
            | Simpan seluruh perubahan ke sessionStorage
            |--------------------------------------------------------------------------
            */
            function saveTemporaryData() {
                const data = {};
                editableElements.forEach(element => {
                    const field = element.dataset.field;
                    if (!field) {
                        return;
                    }
                    data[field] = element.innerHTML;
                });
                sessionStorage.setItem(
                    storageKey,
                    JSON.stringify(data)
                );
            }
            /*
            |--------------------------------------------------------------------------
            | Load data sementara
            |--------------------------------------------------------------------------
            */
            loadTemporaryData();
            /*
            |--------------------------------------------------------------------------
            | Setiap kali user mengedit surat
            |--------------------------------------------------------------------------
            */
            editableElements.forEach(element => {
                element.addEventListener('input', function() {
                    saveTemporaryData();
                });
            });
            /*
            |--------------------------------------------------------------------------
            | Tombol Cetak
            |--------------------------------------------------------------------------
            */
            document
                .getElementById('btn-print')
                .addEventListener('click', function() {
                    /*
                    | Pastikan perubahan terakhir tersimpan
                    */
                    saveTemporaryData();
                    /*
                    | Buka halaman print
                    */
                    const printUrl = @json(route('history.print', $history->id));
                    window.open(
                        printUrl,
                        '_blank'
                    );
                });
        });
    </script>
@endsection
