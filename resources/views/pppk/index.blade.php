@extends('layouts.app')
@section('content')
    @php
        $user = auth()->user();
        $create = $user->role === 'super_admin' || $user->hasPermission('pppk.create');
        $edit = $user->role === 'super_admin' || $user->hasPermission('pppk.update');
    @endphp
    <div class="w-full p-4 md:p-6">
        <!-- HEADER -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Data Golongan PPPK</h1>
                <p class="text-slate-500 mt-1">Kelola Seluruh Data Golongan PPPK Dalam Sistem.</p>
            </div>
            @if ($create)
                <button type="button" onclick="openModal('modalTambahPangkat')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-2xl font-medium shadow-lg">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Tambah Golongan PPPK
                </button>
            @endif
        </div>
        <!-- CARD -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            @php
                $messages = collect(['success', 'danger'])->filter(fn($type) => session()->has($type));
            @endphp
            @if ($messages->isNotEmpty())
                <div class="p-5 space-y-3">
                    @foreach ($messages as $message)
                        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
                            class="flex items-start justify-between rounded-xl border p-4 shadow-sm
                {{ $message === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800' }}">
                            <div class="flex items-center gap-3">
                                @if ($message === 'success')
                                    <i class="fa-solid fa-circle-check text-lg text-green-600"></i>
                                @else
                                    <i class="fa-solid fa-circle-exclamation text-lg text-red-600"></i>
                                @endif
                                <span class="font-medium">
                                    {{ session($message) }}
                                </span>
                            </div>
                            <button type="button" @click="show = false"
                                class="ml-4 text-xl leading-none opacity-60 transition hover:opacity-100">
                                &times;
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
            <!-- TABLE -->
            <div class="w-full overflow-x-auto p-5">
                <table id="pppk-table" class=" js-datatable table-auto min-w-full text-sm text-left text-gray-700">
                    <thead class="bg-slate-50">
                        <tr class="text-sm text-slate-600">
                            <th class="w-20 px-6 py-4 text-left">NO</th>
                            <th class="w-48 px-6 py-4 text-left">Golongan</th>
                            <th class="px-6 py-4 text-left">Nama Golongan</th>
                            <th class="px-6 py-4 text-left">Urutan Golongan</th>
                            @if ($edit)
                                <th class="w-40 px-6 py-4 text-center">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($data as $pppk)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-medium text-slate-700">
                                    {{ $loop->iteration }}
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-700">
                                    {{ $pppk->kode }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $pppk->nama }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $pppk->urutan_golongan }}
                                </td>
                                <td class="w-40 px-6 py-4" data-ignore="true">
                                    <div class="flex items-center justify-start gap-2">
                                        @if (
                                            !((string) $pppk->kode === '0' && (int) $pppk->urutan_golongan === 0) && $edit)
                                            <button type="button"
                                                onclick="openModal('modalEditPangkat{{ $pppk->kode }}')"
                                                title="Edit Golongan PPPK"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                        @endif
                                        {{--
                                        @if ($user->role === 'super_admin')
                                            <button type="button" onclick="openModal('modalHapusPangkat{{ $pppk->id }}')"
                                                class=" w-9 h-9 flex items-center justify-center rounded-xl bg-red-50 text-red-600 hover:bg-red-100 ">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        @endif
                                        --}}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-16 text-slate-400">
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
    @include('pppk.create')
    @include('pppk.edit')
    @include('pppk.delete')
    {{-- @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modalId = sessionStorage.getItem('activeModal');
                if (modalId) {
                    const modal = document.getElementById(modalId);
                    if (modal) {
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    }
                }
            });
        </script>
    @endif --}}
@endsection
