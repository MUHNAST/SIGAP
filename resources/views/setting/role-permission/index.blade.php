@extends('layouts.app')
@section('content')
    @php
        $moduleLabels = [
            'pegawai' => 'Data Pegawai',
            'unit_kerja' => 'Unit Kerja',
            'jabatan' => 'Jabatan',
            'pangkat' => 'Golongan dan Pangkat',
            'pppk' => 'Golongan PPPK',
            'gaji' => 'Data Gaji',
            'laporan_kgb' => 'Laporan KGB',
            'riwayat_kgb' => 'Riwayat Laporan KGB',
            'pengguna' => 'Pengguna',
        ];
        $moduleIcons = [
            'pegawai' => 'fa-users',
            'unit_kerja' => 'fa-building',
            'jabatan' => 'fa-briefcase',
            'pangkat' => 'fa-ranking-star',
            'pppk' => 'fa-user-tie',
            'gaji' => 'fa-money-bill-wave',
            'laporan_kgb' => 'fa-file-lines',
            'riwayat_kgb' => 'fa-clock-rotate-left',
            'pengguna' => 'fa-user-gear',
        ];
        // Pisahkan modul biasa dari modul KGB.
        $regularModules = $permissions->filter(
            fn($modulePermissions, $module) => !in_array($module, ['laporan_kgb', 'riwayat_kgb'], true),
        );
        // Gabungkan kedua modul KGB dalam satu panel.
        $kgbModules = $permissions->filter(
            fn($modulePermissions, $module) => in_array($module, ['laporan_kgb', 'riwayat_kgb'], true) &&
                $modulePermissions->isNotEmpty(),
        );
        $selectedPermissionIds = array_map('intval',old('permission_ids', $selectedPermissionIds ?? []));
        $allPermissions = $permissions->flatten();
        $totalPermissions = $allPermissions->count();
        $visiblePermissionIds = $allPermissions->pluck('id')->map(fn($id) => (int) $id)->all();
        $activePermissions = count(array_intersect($selectedPermissionIds, $visiblePermissionIds));
    @endphp
    <div class="min-h-full w-full space-y-6 p-4 md:p-6 lg:p-8">
        {{-- HEADER --}}
        <header class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="absolute inset-y-0 right-0 hidden w-1/3 bg-gradient-to-l from-blue-50 to-transparent lg:block"></div>
            <div class="relative flex flex-col gap-5 p-5 sm:p-7 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
                        <i class="fa-solid fa-shield-halved text-2xl"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                Pengaturan Sistem
                            </span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                Role: Admin
                            </span>
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">
                            {{ $title ?? 'Manajemen Hak Akses' }}
                        </h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                            {{ $sub_title ?? 'Atur akses menu dan fitur yang tersedia bagi pengguna Admin.' }}
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-3 rounded-2xl border border-blue-100 bg-blue-50/80 p-4">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm">
                        <i class="fa-solid fa-user-shield text-lg"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800">Super Admin</p>
                        <p class="mt-1 text-xs text-slate-500">Pengelola hak akses</p>
                    </div>
                    <i class="fa-solid fa-circle-check ml-2 text-lg text-blue-600"></i>
                </div>
            </div>
        </header>
        {{-- NOTIFIKASI --}}
        @php
            $messages = collect(['success', 'danger'])->filter(fn($type) => session()->has($type));
        @endphp
        @if ($messages->isNotEmpty())
            <div class="space-y-3">
                @foreach ($messages as $message)
                    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
                        class="flex items-start justify-between gap-3 rounded-2xl border p-4 shadow-sm
                        {{ $message === 'success'
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                            : 'border-red-200 bg-red-50 text-red-800' }}">
                        <div class="flex items-start gap-3">
                            <i
                                class="fa-solid {{ $message === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-red-600' }} mt-0.5 text-lg"></i>
                            <span class="text-sm font-medium leading-6">
                                {{ session($message) }}
                            </span>
                        </div>
                        <button type="button" @click="show = false"
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition hover:bg-black/5"
                            aria-label="Tutup notifikasi">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
        {{-- VALIDASI --}}
        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-circle-exclamation mt-0.5 text-lg text-red-600"></i>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold">Periksa kembali Manajemen Hak Akses</p>
                        <ul class="mt-2 list-inside list-disc space-y-1 text-sm leading-6">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        {{-- RINGKASAN --}}
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            {{-- TOTAL HAK AKSES --}}
            <div
                class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">Total Hak Akses</p>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                            {{ $totalPermissions }}
                        </p>
                        <p class="mt-2 text-xs text-slate-400">
                            Izin yang dapat dikonfigurasi
                        </p>
                    </div>
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 transition group-hover:bg-blue-100">
                        <i class="fa-solid fa-list-check text-xl"></i>
                    </div>
                </div>
            </div>
            {{-- HAK AKSES AKTIF --}}
            <div
                class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">Hak Akses Aktif</p>
                        <p id="active-permission-count" class="mt-2 text-3xl font-bold tracking-tight text-emerald-600">
                            {{ $activePermissions }}
                        </p>
                        <p class="mt-2 text-xs text-slate-400">
                            Izin yang dipilih untuk Admin
                        </p>
                    </div>
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 transition group-hover:bg-emerald-100">
                        <i class="fa-solid fa-shield-halved text-xl"></i>
                    </div>
                </div>
            </div>
            {{-- ROLE YANG DIATUR --}}
            <div
                class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">Role yang Diatur</p>
                        <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                            Admin
                        </p>
                        <p class="mt-2 text-xs text-slate-400">
                            Akses operasional pengguna
                        </p>
                    </div>
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 transition group-hover:bg-violet-100">
                        <i class="fa-solid fa-user-gear text-xl"></i>
                    </div>
                </div>
            </div>
        </section>
        {{-- INFORMASI --}}
        <section class="rounded-2xl border border-amber-200 bg-amber-50/80 p-4 sm:p-5">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-amber-600 shadow-sm">
                    <i class="fa-solid fa-circle-info text-lg"></i>
                </div>
                <div class="min-w-0">
                    <h2 class="font-bold text-amber-900">Informasi hak akses Admin</h2>
                    <p class="text-sm leading-6 text-amber-800">
                        Akses dashboard, penjadwalan kenaikan pangkat, KGB, masa pensiun,
                        notifikasi, log aktivitas, dan pengaturan akun merupakan akses
                        bawaan Admin. Pengaturan pada halaman ini hanya digunakan untuk
                        membatasi akses menu dan fitur operasional berdasarkan unit kerja.
                    </p>
                </div>
            </div>
        </section>
        {{-- FORM DAFTAR HAK AKSES --}}
        <form id="role-permission-form" action="{{ route('setting.role-permissions.update') }}" method="POST">
            @csrf
            @method('PUT')
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                {{-- TOOLBAR --}}
                <div class="border-b border-slate-200 p-5 sm:p-6">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                    <i class="fa-solid fa-sliders"></i>
                                </span>
                                <h2 class="text-lg font-bold text-slate-900">
                                    Daftar Izin Admin
                                </h2>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-500">
                                Pilih menu dan fitur operasional yang boleh diakses oleh Admin.
                            </p>
                        </div>
                        <div class="flex w-full flex-col gap-3 sm:flex-row xl:w-auto">
                            <div class="relative min-w-0 flex-1 xl:w-72">
                                <i
                                    class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input id="permission-search" type="text" placeholder="Cari menu atau izin..."
                                    aria-label="Cari hak akses"
                                    class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-10 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                            </div>
                            <button type="button" id="select-all-permissions"
                                class="inline-flex shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                <i class="fa-solid fa-check-double mr-2"></i>
                                Pilih Semua
                            </button>
                        </div>
                    </div>
                </div>
                {{-- DAFTAR MODUL --}}
                <div class="space-y-5 bg-slate-50/60 p-4 sm:p-6">
                    {{-- MODUL SELAIN KGB --}}
                    @forelse ($regularModules as $module => $modulePermissions)
                        @php
                            $modulePermissionIds = $modulePermissions->pluck('id')->map(fn($id) => (int) $id)->all();
                            $moduleSelectedCount = count(array_intersect($modulePermissionIds, $selectedPermissionIds));
                            $moduleLabel = $moduleLabels[$module] ?? ucwords(str_replace('_', ' ', $module));
                        @endphp
                        <section
                            class="permission-module overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-slate-300"
                            data-module="{{ strtolower($moduleLabel) }}">
                            <div
                                class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                        <i class="fa-solid {{ $moduleIcons[$module] ?? 'fa-folder' }} text-xl"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-slate-800">{{ $moduleLabel }}</h3>
                                        <p class="module-summary mt-1 text-xs text-slate-500">
                                            {{ $moduleSelectedCount }} dari {{ $modulePermissions->count() }} izin aktif
                                        </p>
                                    </div>
                                </div>
                                <label
                                    class="inline-flex w-fit cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                    <input type="checkbox"
                                        class="module-select h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    Pilih semua modul
                                </label>
                            </div>
                            <div class="permission-list grid grid-cols-1 gap-3 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-3">
                                @foreach ($modulePermissions as $permission)
                                    @php
                                        $isSelected = in_array((int) $permission->id, $selectedPermissionIds, true);
                                        $permissionName = $permission->nama ?? $permission->kode;
                                        $permissionDescription = $permission->deskripsi ?? '';
                                        $searchText = strtolower(
                                            $permissionName .
                                                ' ' .
                                                $permissionDescription .
                                                ' ' .
                                                $permission->kode .
                                                ' ' .
                                                $moduleLabel,
                                        );
                                    @endphp
                                    <label
                                        class="permission-item group flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition duration-150 {{ $isSelected ? 'border-blue-300 bg-blue-50/60 ring-1 ring-blue-100' : 'border-slate-200 bg-white hover:border-blue-200 hover:bg-blue-50/30' }}"
                                        data-search="{{ $searchText }}">
                                        <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}"
                                            class="permission-checkbox mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                            @checked($isSelected)>
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold leading-5 text-slate-800">
                                                {{ $permission->nama }}
                                            </span>
                                            <span
                                                class="mt-1.5 inline-flex max-w-full break-all rounded-md bg-slate-100 px-2 py-1 font-mono text-[11px] text-slate-500 group-hover:bg-white">
                                                {{ $permission->kode }}
                                            </span>
                                            @if ($permission->deskripsi)
                                                <span class="mt-2 block text-xs leading-5 text-slate-500">
                                                    {{ $permission->deskripsi }}
                                                </span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">
                            Tidak ada modul operasional selain KGB.
                        </div>
                    @endforelse
                    {{-- SATU PANEL UNTUK LAPORAN KGB DAN RIWAYAT KGB --}}
                    @if ($kgbModules->isNotEmpty())
                        <section class="permission-module rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                            data-module="pengelolaan kgb laporan kgb riwayat laporan kgb">
                            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                        <i class="fa-solid fa-file-lines text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-slate-800">
                                            Pengelolaan KGB
                                        </h3>
                                        <p class="text-sm text-slate-500">
                                            Pengaturan akses laporan dan riwayat laporan KGB.
                                        </p>
                                    </div>
                                </div>
                                <label
                                    class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-600">
                                    <input type="checkbox"
                                        class="module-select h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        aria-label="Pilih semua izin Pengelolaan KGB">
                                    Pilih semua
                                </label>
                            </div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                @foreach ($kgbModules as $module => $modulePermissions)
                                    @foreach ($modulePermissions as $permission)
                                        @php
                                            $isSelected = in_array((int) $permission->id, $selectedPermissionIds, true);
                                            $permissionName = $permission->nama ?? $permission->kode;
                                            $permissionDescription = $permission->deskripsi ?? '';
                                            $searchText = strtolower(
                                                $permissionName .
                                                    ' ' .
                                                    $permissionDescription .
                                                    ' ' .
                                                    $permission->kode .
                                                    ' ' .
                                                    ($moduleLabels[$module] ?? $module),
                                            );
                                        @endphp
                                        <label
                                            class="permission-item group flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition duration-150 {{ $isSelected ? 'border-blue-300 bg-blue-50/60 ring-1 ring-blue-100' : 'border-slate-200 bg-white hover:border-blue-200 hover:bg-blue-50/30' }}"
                                            data-search="{{ $searchText }}">
                                            <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}"
                                                class="permission-checkbox mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                {{ $isSelected ? 'checked' : '' }}>
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-semibold text-slate-800">
                                                    {{ $permissionName }}
                                                </span>
                                                @if ($permissionDescription !== '')
                                                    <span class="mt-1 block text-xs leading-5 text-slate-500">
                                                        {{ $permissionDescription }}
                                                    </span>
                                                @endif
                                                <span
                                                    class="mt-2 inline-block rounded-md bg-slate-100 px-2 py-1 text-xs text-slate-500">
                                                    {{ $moduleLabels[$module] ?? ucfirst(str_replace('_', ' ', $module)) }}
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                @endforeach
                            </div>
                            <div class="module-summary mt-4 text-right text-xs text-slate-500">
                                <!-- Jumlah izin diperbarui melalui JavaScript -->
                            </div>
                        </section>
                    @endif
                    {{-- MODUL KHUSUS SUPER ADMIN --}}
                    <section class="overflow-hidden rounded-2xl border border-red-200 bg-white">
                        <div
                            class="flex flex-col gap-4 bg-gradient-to-r from-red-50 to-white p-4 sm:flex-row sm:items-center sm:p-5">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-red-100 bg-white text-red-600 shadow-sm">
                                <i class="fa-solid fa-shield-halved text-xl"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-bold text-slate-800">Manajemen Hak Akses</h3>
                                    <span
                                        class="rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-semibold text-red-700">
                                        Super Admin
                                    </span>
                                </div>
                                <p class="mt-1 text-sm leading-6 text-slate-500">
                                    Pengelolaan izin pengguna hanya tersedia bagi Super Admin
                                    dan tidak dapat diberikan kepada Admin melalui halaman ini.
                                </p>
                            </div>
                            <div
                                class="inline-flex w-fit shrink-0 items-center gap-2 rounded-xl border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700">
                                <i class="fa-solid fa-lock"></i>
                                Akses terkunci
                            </div>
                        </div>
                    </section>
                </div>
                {{-- FOOTER --}}
                <div
                    class="flex flex-col gap-4 border-t border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="flex items-start gap-2.5 text-slate-500">
                        <i class="fa-solid fa-circle-info mt-0.5 text-blue-500"></i>
                        <p class="text-xs leading-5">
                            Perubahan hak akses akan diterapkan setelah disimpan.
                            Pastikan pilihan izin sudah sesuai kebutuhan Admin.
                        </p>
                    </div>
                    <button type="submit"
                        class="inline-flex w-full shrink-0 items-center justify-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200 sm:w-auto">
                        <i class="fa-solid fa-floppy-disk mr-2"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
