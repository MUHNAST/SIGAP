@extends('layouts.app')
@section('content')
    @php
        $user = auth()->user();
        $view = $user->role === 'super_admin' || $user->hasPermission('laporan_kgb.manage');
    @endphp
    <div class="p-6">
        <!-- HEADER -->
        @if ($view)
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800">
                        Laporan Kenaikan Gaji Berkala (KGB)
                    </h1>
                    <p class="text-slate-500 mt-1">
                        Menampilkan daftar pegawai yang memenuhi syarat kenaikan gaji berkala berdasarkan periode yang
                        dipilih.
                    </p>
                </div>
            </div>
            <!-- CARD LAPORAN -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl bg-purple-100 flex items-center justify-center">
                                <i class="fa-solid fa-money-bill-wave text-3xl text-purple-600"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-slate-800">
                                    Laporan Kenaikan Gaji Berkala
                                </h2>
                                <p class="text-slate-500">
                                    Daftar pegawai yang akan memperoleh kenaikan gaji berkala dalam 2 bulan ke depan.
                                </p>
                            </div>
                        </div>
                        <span class="px-4 py-2 rounded-full bg-green-100 text-green-700 font-semibold">
                            {{ $jumlahKgb ?? 0 }} Pegawai
                        </span>
                    </div>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="rounded-2xl bg-slate-50 p-5">
                            <p class="text-slate-500 text-sm">
                                Jumlah Pegawai
                            </p>
                            <h3 class="text-3xl font-bold text-slate-800 mt-2">
                                {{ $jumlahKgb ?? 0 }}
                            </h3>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-5">
                            <p class="text-slate-500 text-sm">
                                Periode
                            </p>
                            <h3 class="text-xl font-bold text-slate-800 mt-2">
                                2 Bulan Mendatang
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm mt-6 overflow-hidden">
                <div class="w-full overflow-x-auto p-5">
                    <table id="report-table" class="js-datatable table-auto min-w-full text-sm text-left text-gray-700">
                        <thead class="bg-slate-50">
                            <tr class="text-sm text-slate-600">
                                <th class="w-20 px-6 py-4">NO</th>
                                <th class="px-6 py-4">NIP</th>
                                <th class="px-6 py-4">Nama Pegawai</th>
                                <th class="px-6 py-4">Pangkat</th>
                                <th class="px-6 py-4">Jabatan</th>
                                <th class="px-6 py-4">Tanggal KGB</th>
                                @if ($view)
                                    <th class="px-6 py-4 text-center">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($data as $pegawai)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 font-medium">
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $pegawai->nip }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $pegawai->nama }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $pegawai->rank?->nama ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $pegawai->position?->nama ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 font-medium text-blue-600">
                                        {{ $pegawai->tanggal_kgb?->translatedFormat('d F Y') ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            @if ($view)
                                                <a href="{{ route('report.preview', $pegawai->nip) }}"
                                                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            @endif
                                            {{-- <a href="{{ route('report.print', $pegawai->id) }}" target="_blank"
                                                class="w-9 h-9 flex items-center justify-center rounded-xl bg-green-50 text-green-600 hover:bg-green-100">
                                                <i class="fa-solid fa-print"></i>
                                            </a> --}}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-16 text-slate-400">
                                        <i class="fa-solid fa-folder-open text-3xl mb-3 block"></i>
                                        Tidak ada pegawai yang akan memperoleh KGB dalam 2 bulan ke depan.
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
                    Data KGB belum tersedia.
                </h3>
            </div>
        @endif
    </div>
@endsection
