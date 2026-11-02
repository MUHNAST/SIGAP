@extends('layouts.app')
@section('content')
    @php
        $user = auth()->user();
        $view = $user->hasPermission('pegawai.view');
    @endphp
    <div class="w-full p-4 md:p-6">
        <!-- HEADER -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">
                    {{ $data['title'] }}
                </h1>
                <p class="text-slate-500 mt-1">
                    {{ $data['deskripsi'] }}
                </p>
            </div>
        </div>
        <!-- CARD -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- TABLE -->
            <div class="w-full overflow-x-auto p-5">
                <table id="career-table" class="js-datatable min-w-full table-auto text-sm text-left text-gray-700">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left">
                                NO
                            </th>
                            <th class="px-6 py-4 text-left">
                                NIP
                            </th>
                            <th class="px-6 py-4 text-left">
                                Nama
                            </th>
                            <th class="px-6 py-4 text-left">
                                Status Pegawai
                            </th>
                            <th class="px-6 py-4 text-left">
                                Pangkat/Golongan
                            </th>
                            <th class="px-6 py-4 text-left">
                                Masa Kerja
                            </th>
                            <th class="px-6 py-4 text-left">
                                {{ $data['dateLabel'] }}
                            </th>
                            @if ($view)
                                <th class="px-6 py-4 text-center" data-ignore="true">
                                    Aksi
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($data['data'] as $employee)
                            <tr class="hover:bg-slate-50 transition">
                                {{-- NO --}}
                                <td class="px-6 py-4 font-medium text-slate-700">
                                    {{ $loop->iteration }}
                                </td>
                                {{-- NIP --}}
                                <td class="px-6 py-4 font-medium text-slate-700">
                                    {{ $employee->nip }}
                                </td>
                                {{-- NAMA --}}
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
                                                {{ $employee->position?->nama ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                {{-- JABATAN --}}
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
                                {{-- PANGKAT --}}
                                <td class="px-6 py-4">
                                    {{ $employee->tahun_masa_kerja }} Tahun
                                    {{ $employee->bulan_masa_kerja }} Bulan
                                </td>
                                {{-- TMT --}}
                                <td class="px-6 py-4">
                                    <span class="hidden">
                                        {{ $employee->{$data['dateField']} }}
                                    </span>
                                    {{ $employee->{$data['dateField']}
                                        ? \Carbon\Carbon::parse($employee->{$data['dateField']})->translatedFormat('d M Y')
                                        : '-' }}
                                </td>
                                @if ($view)
                                    {{-- AKSI --}}
                                    <td class="px-6 py-4" data-ignore="true">
                                        <div class="flex justify-center">
                                            <a href="{{ route('employee.show', $employee->nip) }}"
                                                title="Lihat Data Pegawai"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-16 text-slate-400">
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
@endsection
