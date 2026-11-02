@extends('layouts.app')
@section('content')
    @php
        $user = auth()->user();
    $canResetPassword = $user->role === 'super_admin'  || $user->hasPermission('user.reset_password');
    @endphp
    <div class="w-full p-4 md:p-6">
        <!-- HEADER -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Data Admin dan User</h1>
                <p class="text-slate-500 mt-1">Kelola Seluruh Data Pengguna Dalam Sistem.</p>
            </div>
            @if ($user->role == 'super_admin')
                <button type="button" onclick="openModal('modalTambahUser')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-2xl font-medium shadow-lg">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Tambah Admin
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
                {{ $message === 'success'
                    ? 'border-green-200 bg-green-50 text-green-800'
                    : 'border-red-200 bg-red-50 text-red-800' }}">
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
                <table id="user-table" class=" js-datatable table-auto min-w-full text-sm text-left text-gray-700">
                    <thead class="bg-slate-50">
                        <tr class="text-sm text-slate-600">
                            <th class="w-20 px-6 py-4 text-left">NO</th>
                            <th class="w-48 px-6 py-4 text-left">Foto</th>
                            <th class="w-48 px-6 py-4 text-left">Nama</th>
                            <th class="px-6 py-4 text-left">Username</th>
                            <th class="px-6 py-4 text-left">Email</th>
                            <th class="px-6 py-4 text-left">Nomor Hp</th>
                            <th class="w-40 px-6 py-4 text-center">Role</th>
                            <th class="w-40 px-6 py-4 text-center">Unit Kerja</th>
                            @if ($canResetPassword)
                                <th class="w-20 px-6 py-4 text-center">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($data as $pengguna)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-medium text-slate-700">
                                    {{ $loop->iteration }}
                                </td>
                                <td class="px-6 py-4">
                                    <img src="{{ !$pengguna->foto || $pengguna->foto === 'images/default-user.png'
                                        ? asset('images/default-user.png')
                                        : asset('storage/' . $pengguna->foto) }}"
                                        class="w-10 h-10 rounded-xl object-cover border">
                                </td>
                                <td class="px-6 py-4">
                                    {{ $pengguna->nama }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $pengguna->username }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $pengguna->email }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $pengguna->no_hp }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ match ($pengguna->role) {
                                        'super_admin' => 'Super Admin',
                                        'admin' => 'Admin',
                                        'user' => 'User',
                                        default => 'Tidak Diketahui',
                                    } }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $pengguna->department?->nama }}
                                </td>
                                <td class="w-40 px-6 py-4" data-ignore="true">
                                    <div class="flex items-center gap-2">
                                        {{-- Reset Password --}}
                                        @if ($canResetPassword)
                                            <button type="button"
                                                onclick="openModal('modalResetPassword{{ $pengguna->id }}')"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-green-50 text-green-500 hover:bg-green-100"
                                                title="Reset Password">
                                                <i class="fa-solid fa-key"></i>
                                            </button>
                                        @endif
                                        {{-- Edit: hanya Super Admin dan bukan akun User --}}
                                        @if ($user->role === 'super_admin' && $pengguna->role !== 'user')
                                            <button type="button" onclick="openModal('modalEditUser{{ $pengguna->id }}')"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100"
                                                title="Edit Pengguna">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-16 text-slate-400">
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
    @include('user.edit')
    @include('user.resetPassword')
    @include('user.create')
    {{-- @include('user.delete') --}}
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
    @if (session('open_modal'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                openModal('{{ session('open_modal') }}');
            });
        </script>
    @endif
@endsection
