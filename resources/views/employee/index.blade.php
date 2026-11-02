@extends('layouts.app')
@section('content')
    <div class="w-full p-4 md:p-6">
        @php
            $user = auth()->user();

            $view = $user->hasPermission('pegawai.view');
            $create = $user->hasPermission('pegawai.create');
            $edit = $user->hasPermission('pegawai.update');
            $export = $user->hasPermission('pegawai.export');
            $import = $user->hasPermission('pegawai.import_template');
        @endphp
        <!-- HEADER -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Data Pegawai</h1>
                <p class="text-slate-500 mt-1">Kelola seluruh data pegawai dalam sistem.</p>
            </div>
            @if ($create)
                <button type="button" onclick="openModal('modalTambahPegawai')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-2xl font-medium shadow-lg">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Tambah Pegawai
                </button>
            @endif
        </div>
        <!-- CARD -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            @if ($export || $import)
                <div class="flex flex-wrap gap-2 p-5 border-b border-slate-200">
                    <div class="flex items-center gap-2">
                        @if ($export)
                            <div class="relative">
                                <button id="exportDropdownButton" data-dropdown-toggle="exportDropdown" type="button"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:ring-4 focus:ring-blue-200">
                                    Export as
                                    <svg class="w-4 h-4 ms-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="m19 9-7 7-7-7" />
                                    </svg>
                                </button>
                                <div id="exportDropdown"
                                    class="hidden z-10 w-56 bg-white rounded-xl shadow-lg border border-gray-200">
                                    <ul class="py-2 text-sm text-gray-700">
                                        <li>
                                            <button type="button" id="openExportModal"
                                                class="flex items-center w-full px-4 py-2 hover:bg-gray-100">
                                                <i class="fa-solid fa-file-export w-5 mr-2 text-blue-600"></i>
                                                Export Data Pegawai
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div id="exportModal"
                                class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/60 p-3 backdrop-blur-sm sm:p-5">
                                <div
                                    class="flex max-h-[94vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                                    {{-- Header --}}
                                    <div
                                        class="flex shrink-0 items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                                        <div>
                                            <h3 class="text-xl font-bold text-slate-800">
                                                Export Data Pegawai
                                            </h3>
                                            <p class="mt-1 text-sm text-slate-500">
                                                Pilih kolom dan format file yang akan diekspor.
                                            </p>
                                        </div>
                                        <button type="button" id="closeExportModal"
                                            class="flex h-10 w-10 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                            aria-label="Tutup modal">
                                            <i class="fa-solid fa-xmark text-xl"></i>
                                        </button>
                                    </div>
                                    <form id="exportForm"
                                        data-export-url="{{ route('employee.export', ['format' => 'xlsx']) }}"
                                        method="GET" class="flex min-h-0 flex-1 flex-col">
                                        {{-- Isi modal --}}
                                        <div class="min-h-0 flex-1 overflow-y-auto p-5 sm:p-6">
                                            {{-- Pilihan kolom --}}
                                            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <h4 class="font-semibold text-slate-800">
                                                        Pilihan Kolom
                                                    </h4>
                                                    <p class="mt-1 text-xs text-slate-500">
                                                        Pilih kolom sesuai kebutuhan. Ekspor PDF dibatasi maksimal 9 kolom.
                                                    </p>
                                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                                        <span
                                                            class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700">
                                                            <i class="fa-solid fa-check-square"></i>
                                                            Dipilih:
                                                            <span id="selectedColumnCount">0</span>
                                                            kolom
                                                        </span>
                                                        <span
                                                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                                            Total:
                                                            <span id="totalColumnCount">0</span>
                                                            kolom
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="flex flex-wrap gap-4 text-sm">
                                                    <button type="button" id="selectAllColumns"
                                                        class="font-semibold text-blue-600 hover:text-blue-800">
                                                        <i class="fa-solid fa-check-double mr-1"></i>
                                                        Pilih Semua
                                                    </button>
                                                    <button type="button" id="deselectAllColumns"
                                                        class="font-semibold text-red-600 hover:text-red-800">
                                                        <i class="fa-solid fa-square-minus mr-1"></i>
                                                        Hapus Semua
                                                    </button>
                                                </div>
                                            </div>
                                            {{-- Daftar kolom --}}
                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                                @foreach ($exportGroups as $group => $columns)
                                                    <section class="rounded-xl border border-slate-200 p-4">
                                                        <h5
                                                            class="mb-3 border-b border-slate-100 pb-3 text-xs font-bold uppercase tracking-wider text-slate-500">
                                                            {{ $group }}
                                                        </h5>
                                                        <div class="space-y-1">
                                                            @foreach ($columns as $key => $label)
                                                                <label
                                                                    class="flex cursor-pointer items-center gap-3 rounded-lg border border-transparent px-3 py-2 transition hover:border-blue-100 hover:bg-blue-50">
                                                                    <input type="checkbox" name="columns[]"
                                                                        value="{{ $key }}" checked
                                                                        class="export-column h-4 w-4 shrink-0 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                                                    <span class="text-sm text-slate-700">
                                                                        {{ $label }}
                                                                    </span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </section>
                                                @endforeach
                                            </div>
                                            <p id="exportColumnError"
                                                class="mt-4 hidden rounded-lg border border-red-200 bg-red-50 p-3 text-sm
                                            text-red-700"
                                                role="alert" aria-live="polite">
                                                Pilih minimal satu kolom untuk melanjutkan ekspor.
                                            </p>
                                            <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                                                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <div
                                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600">
                                                            <i class="fa-solid fa-file-pdf text-lg"></i>
                                                        </div>
                                                        <div>
                                                            <h4 class="font-semibold text-slate-800">
                                                                Pengaturan PDF
                                                            </h4>
                                                            <p class="mt-1 text-xs text-slate-500">
                                                                Pengaturan otomatis untuk unduhan PDF.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-3">
                                                    <div class="rounded-xl border border-slate-200 p-4">
                                                        <p class="text-xs text-slate-500">Orientasi</p>
                                                        <p class="mt-1 font-semibold text-slate-800">Landscape</p>
                                                    </div>
                                                    <div class="rounded-xl border border-slate-200 p-4">
                                                        <p class="text-xs text-slate-500">Ukuran kertas</p>
                                                        <p class="mt-1 font-semibold text-slate-800">A4</p>
                                                    </div>
                                                    <div class="rounded-xl border border-slate-200 p-4">
                                                        <p class="text-xs text-slate-500">Maksimal kolom PDF</p>
                                                        <p class="mt-1 font-semibold text-slate-800">9 kolom</p>
                                                    </div>
                                                </div>
                                                <p class="px-5 pb-5 text-xs leading-relaxed text-slate-500">
                                                    PDF akan langsung diunduh setelah tombol Export PDF diklik.
                                                    Pilih maksimal 9 kolom sebelum memulai ekspor.
                                                </p>
                                            </section>
                                        </div>
                                        {{-- Footer --}}
                                        <div
                                            class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:px-6">
                                            <p class="hidden text-xs text-slate-500 lg:block">
                                                <i class="fa-solid fa-circle-info mr-1"></i>
                                                Pilih kolom sebelum menentukan format ekspor.
                                            </p>
                                            <div class="ml-auto flex flex-wrap justify-end gap-2">
                                                <button type="button" id="cancelExport"
                                                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                                    Batal
                                                </button>
                                                <button type="button" data-format="csv"
                                                    class="export-submit inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                                    <i class="fa-solid fa-file-csv mr-2"></i>
                                                    CSV
                                                </button>
                                                <button type="button" data-format="xlsx"
                                                    class="export-submit inline-flex items-center rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-green-700">
                                                    <i class="fa-solid fa-file-excel mr-2"></i>
                                                    Excel
                                                </button>
                                                <button type="button" data-format="pdf"
                                                    class="export-submit inline-flex items-center rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                                                    <i class="fa-solid fa-file-pdf mr-2"></i>
                                                    Export PDF
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                        @if ($import)
                            {{-- Import --}}
                            <button type="button" data-modal-target="importModal" data-modal-toggle="importModal"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg shadow-sm hover:bg-blue-700 focus:ring-4 focus:ring-blue-200">
                                <i class="fa-solid fa-file-import mr-2"></i>
                                Import Data
                            </button>
                        @endif
                    </div>
                </div>
            @endif
            @if (session('success'))
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show"
                    class="flex items-start justify-between rounded-xl border p-4 shadow-sm border-green-200 bg-green-50 text-green-800">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-check text-lg text-green-600"></i>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                    <button type="button" @click="show = false" class="ml-4 text-xl opacity-60 hover:opacity-100">
                        &times;
                    </button>
                </div>
            @endif
            @if (session('danger'))
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show"
                    class="flex items-start justify-between rounded-xl border p-4 shadow-sm border-red-200 bg-red-50 text-red-800">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-exclamation text-lg text-red-600"></i>
                        <span class="font-medium">{{ session('danger') }}</span>
                    </div>
                    <button type="button" @click="show = false" class="ml-4 text-xl opacity-60 hover:opacity-100">
                        &times;
                    </button>
                </div>
            @endif
            <!-- TABLE -->
            <div class="w-full overflow-x-auto p-5">
                <table id="pegawai-table" class="js-datatable min-w-full table-auto text-sm text-left text-gray-700">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left">NO</th>
                            <th class="px-6 py-4 text-left">NIP</th>
                            <th class="px-6 py-4 text-left">Nama</th>
                            <th class="px-6 py-4 text-left">Status Pegawai</th>
                            <th class="px-6 py-4 text-left">Pangkat/Golongan</th>
                            <th class="px-6 py-4 text-left">TMT Pangkat</th>
                            <th class="px-6 py-4 text-left">TMT KGB</th>
                            <th class="px-6 py-4 text-left">TMT Pensiun</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            @if ($edit || $view)
                                <th class="px-6 py-4 text-center" data-ignore="true">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($data as $employee)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-medium text-slate-700">
                                    {{ $loop->iteration }}
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-700">
                                    {{ $employee->nip }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ !$employee?->foto || $employee->foto === 'images/default-user.png'
                                            ? asset('images/default-user.png')
                                            : asset('storage/' . $employee->foto) }}"
                                            class="w-10 h-10 rounded-xl object-cover border">
                                        <div>
                                            <div class="font-semibold text-slate-800">
                                                {{ $employee->nama }}
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ $employee->position?->nama }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    {{ $employee->status_pegawai }}
                                </td>
                                <td class="px-6 py-4">
                                    @if ($employee->status_pegawai === 'PNS')
                                        {{ $employee->rank->nama ?? '-' }}
                                    @else
                                        {{ $employee->pppk->nama ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="hidden">
                                        {{ $employee->tmt_pangkat_baru }}
                                    </span>
                                    {{ \Carbon\Carbon::parse($employee->tmt_pangkat_baru)->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="hidden">
                                        {{ $employee->tmt_kgb_baru }}
                                    </span>
                                    {{ \Carbon\Carbon::parse($employee->tmt_kgb_baru)->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="hidden">
                                        {{ $employee->tmt_pensiun }}
                                    </span>
                                    {{ $employee->tmt_pensiun ? \Carbon\Carbon::parse($employee->tmt_pensiun)->translatedFormat('d M Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium
                                    {{ $employee->status == 'Aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $employee->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4" data-ignore="true">
                                    <div class="flex justify-center gap-2">
                                        @if ($view)
                                            <a href="{{ route('employee.show', $employee->nip) }}"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        @endif
                                        {{-- <button type="button" onclick="openModal('modalEditPegawai{{ $employee->nip }}')"
                                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100">
                                            <i class="fa-solid fa-pen"></i>
                                        </button> --}}
                                        @if ($edit)
                                            <a href="{{ route('employee.edit', $employee->nip) }}"
                                                class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-amber-100 hover:bg-amber-200 text-amber-600">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            {{-- <button type="button"
                                                onclick="openModal('modalHapusPegawai{{ $employee->nip }}')"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-red-50 text-red-600 hover:bg-red-100">
                                                <i class="fa-solid fa-trash"></i>
                                            </button> --}}
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-16 text-slate-400">
                                    <i class="fa-solid fa-folder-open text-3xl mb-2 block"></i>
                                    Data belum tersedia
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @include('employee.create')
    {{-- @include('employee.delete') --}}
    @include('employee.import')
    @if (session('open_create_modal'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                openModal('modalTambahPegawai');
            });
        </script>
    @endif
    @if (session('edit_employee_nip'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                openModal('modalEditPegawai{{ session('edit_employee_nip') }}');
            });
        </script>
    @endif
@endsection
