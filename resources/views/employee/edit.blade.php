@extends('layouts.app')
@section('content')
    <div class="p-6">
        {{-- Header --}}
        <div class="bg-white rounded-3xl shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <a href="{{ route('employee.index') }}"
                        class="w-12 h-12 rounded-2xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold">
                            Edit Pegawai
                        </h1>
                        <p class="text-slate-500">
                            Perbarui informasi pegawai
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- BODY -->
        <div class="bg-white rounded-3xl shadow-sm">
            <form id="pegawaiForm" enctype="multipart/form-data" method="POST"
                action="{{ route('employee.update', $employee) }}" class="p-6 md:p-8">
                @csrf
                @method('PUT')
                <div class="p-6 md:p-8"">
                    <!-- ===================== INFORMASI PRIBADI ===================== -->
                    <div class="mb-8">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-lg">Informasi Pribadi</h3>
                        </div>
                        <div class="grid md:grid-cols-2 gap-5">
                            <!-- Nama -->
                            <div>
                                <label class="text-sm font-medium text-slate-700 mb-2 block">Nama Lengkap <span
                                        class="text-red-500">*</span></label>
                                <div class="relative">
                                    <i class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="text" placeholder="Masukkan Nama Lengkap" name="nama" maxlength="100"
                                        value="{{ old('nama', $employee->nama) }}"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
                                </div>
                                @error('nama')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <!-- NIK -->
                            <div>
                                <label class="text-sm font-medium text-slate-700 mb-2 block">
                                    NIK
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-id-card absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="text" placeholder="Masukkan NIK" name="nik" maxlength="16"
                                        autocomplete="off" minlength="16" inputmode="numeric" pattern="[0-9]{16}"
                                        value="{{ old('nik', $employee->nik) }}"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
                                </div>
                                @error('nik')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <!-- NIP -->
                            <div>
                                <label class="text-sm font-medium text-slate-700 mb-2 block">NIP <span
                                        class="text-red-500">*</span></label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-hashtag absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input id="nip" type="text" placeholder="Masukkan NIP" name="nip"
                                        autocomplete="off" maxlength="18" minlength="18" pattern="[0-9]{18}"
                                        inputmode="numeric" value="{{ old('nip', $employee->nip) }}" required
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
                                </div>
                                @error('nip')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <!-- HP -->
                            <div>
                                <label class="text-sm font-medium text-slate-700 mb-2 block">No. HP <span
                                        class="text-red-500">*</span></label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="tel" placeholder="08xxxxxxxxxx" name="no_hp" autocomplete="off"
                                        maxlength="14"minlength="10" pattern="08[0-9]{8,12}" inputmode="numeric"
                                        value="{{ old('no_hp', $employee->no_hp) }}"
                                        oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl">
                                </div>
                                @error('no_hp')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <!-- Email -->
                            <div>
                                <label class="text-sm font-medium text-slate-700 mb-2 block">Email</label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="email" placeholder="pegawai@email.com" name="email" minlength="7"
                                        autocomplete="off" maxlength="100" value="{{ old('email', $employee->email) }}"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl">
                                </div>
                                @error('email')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <!-- Foto Pegawai -->
                            <div class="md:col-span-2">
                                <label class="text-sm font-medium text-slate-700 mb-3 block">
                                    Foto Pegawai
                                </label>
                                <div
                                    class="border-2 border-dashed border-slate-300 rounded-3xl p-8 bg-slate-50 hover:border-blue-400 hover:bg-blue-50 transition">
                                    <div class="flex flex-col items-center">
                                        <div class="relative group">
                                            <img id="previewEdit{{ $employee->nip }}"
                                                src="{{ !$employee->foto
                                                    ? asset('images/default-user.png')
                                                    : (str_starts_with($employee->foto, 'images/')
                                                        ? asset($employee->foto)
                                                        : asset('storage/' . $employee->foto)) }}"
                                                class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg">
                                            <label for="fotoEdit{{ $employee->nip }}"
                                                class="absolute bottom-0 right-0 w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-lg cursor-pointer hover:bg-blue-700 transition">
                                                <i class="fa-solid fa-camera"></i>
                                            </label>
                                        </div>
                                        <h4 class="mt-5 font-semibold text-slate-700">
                                            Upload Foto Pegawai
                                        </h4>
                                        <p id="namaFileEdit{{ $employee->nip }}"
                                            class="text-sm text-slate-500 text-center mt-1">
                                            Klik ikon kamera untuk memilih foto<br>
                                            Format JPG, JPEG, PNG • Maksimal 2 MB
                                        </p>
                                        <input type="file" id="fotoEdit{{ $employee->nip }}" name="foto"
                                            accept="image/jpeg,image/jpg,image/png" class="hidden image-preview-input"
                                            data-preview="#previewEdit{{ $employee->nip }}"
                                            data-filename="#namaFileEdit{{ $employee->nip }}">
                                    </div>
                                </div>
                                @error('foto')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <!-- Tempat Lahir -->
                            <div class="relative">
                                <label class="text-sm font-medium text-slate-700 mb-2 block">
                                    Tempat Lahir <span class="text-red-500">*</span>
                                </label>
                                <div class="relative group">
                                    <i
                                        class="fa-solid fa-location-dot z-20 absolute left-4 top-1/2 -translate-y-1/2 text-blue-500 transition-colors group-focus-within:text-blue-600"></i>
                                    <select name="tempat_lahir" class="tempat-lahir-select"
                                        data-selected="{{ $employee->tempat_lahir }}">
                                        <option value="">Pilih Tempat Lahir</option>
                                        @foreach ($master['cities'] as $item)
                                            <option value="{{ $item->id }}"
                                                {{ old('tempat_lahir', $employee->tempat_lahir) == $item->id ? 'selected' : '' }}>
                                                {{ $item->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('tempat_lahir')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <!-- Tanggal Lahir -->
                            <div>
                                <label class="text-sm font-medium text-slate-700 mb-2 block">Tanggal Lahir <span
                                        class="text-red-500">*</span></label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-calendar-days absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="date" name="tanggal_lahir" autocomplete="off"
                                        value="{{ old('tanggal_lahir', optional($employee->tanggal_lahir)->format('Y-m-d')) }}"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
                                </div>
                                @error('tanggal_lahir')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <!-- Jenis Kelamin -->
                            <div class="relative">
                                <label class="text-sm font-medium text-slate-700 mb-2 block">
                                    Jenis Kelamin <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-venus-mars absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <select name="jenis_kelamin"
                                        class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none">
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="Laki-laki"
                                            {{ old('jenis_kelamin', $employee->jenis_kelamin) == 'Laki-laki' ? 'selected' : '' }}>
                                            Laki-laki
                                        </option>
                                        <option value="Perempuan"
                                            {{ old('jenis_kelamin', $employee->jenis_kelamin) == 'Perempuan' ? 'selected' : '' }}>
                                            Perempuan
                                        </option>
                                    </select>
                                </div>
                                @error('jenis_kelamin')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <!-- agama -->
                            <div class="relative">
                                <label class="text-sm font-medium text-slate-700 mb-2 block">Agama </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-mosque absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <select name="agama" value="{{ old('agama') }}"
                                        class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none">
                                        <option value="">Pilih Agama</option>
                                        @foreach (['Islam', 'Kristen Protestan', 'Kristen Katolik', 'Budha', 'Hindu', 'Konghucu'] as $agama)
                                            <option value="{{ $agama }}"
                                                {{ old('agama', $employee->agama) == $agama ? 'selected' : '' }}>
                                                {{ $agama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('agama')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <!-- Status Pernikahan -->
                            <div class="relative">
                                <label class="text-sm font-medium text-slate-700 mb-2 block">Status Pernikahan</label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-heart absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <select name="status_nikah"
                                        class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none">
                                        <option value="">Pilih Status Pernikahan</option>
                                        @foreach (['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'] as $status)
                                            <option value="{{ $status }}"
                                                {{ old('status_nikah', $employee->status_nikah) == $status ? 'selected' : '' }}>
                                                {{ $status }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('status_nikah')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            {{-- Tingkat Pendidikan --}}
                            <div class="relative">
                                <label for="tingkat_pendidikan" class="text-sm font-medium text-slate-700 mb-2 block">
                                    Pendidikan
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-graduation-cap absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                                    <select name="tingkat_pendidikan" id="tingkat_pendidikan" data-ranks-url="{{ route('employee.ranks-by-education', ':kode') }}"
                                        data-selected="{{ old('tingkat_pendidikan', $employee->tingkat_pendidikan) }}"
                                        class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100
                                                outline-none appearance-none @error('tingkat_pendidikan', 'employee') border-red-500 @enderror">
                                        <option value="">Pilih Tingkat Pendidikan</option>
                                        @foreach ($master['educationLevels'] as $education)
                                            <option value="{{ $education->kode }}"
                                                data-pangkat-pns-minimum="{{ $education->pangkat_pns_minimum }}"
                                                data-pangkat-pns-maksimum="{{ $education->pangkat_pns_maksimum }}"
                                                data-golongan-pppk-minimum="{{ $education->golongan_pppk_minimum }}"
                                                data-golongan-pppk-maksimum="{{ $education->golongan_pppk_maksimum }}"
                                                @selected((string) old('tingkat_pendidikan', $employee->tingkat_pendidikan) === (string) $education->kode)>
                                                {{ $education->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('tingkat_pendidikan', 'employee')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <!-- Provinsi -->
                            <div class="relative">
                                <label class="text-sm font-medium text-slate-700 mb-2 block">Provinsi
                                </label>
                                <div class="relative group">
                                    <i
                                        class="fa-solid fa-earth-asia absolute left-4 top-1/2 -translate-y-1/2 text-blue-500 z-20 pointer-events-none transition-colors group-focus-within:text-blue-600">
                                    </i>
                                    <select name="id_provinsi" class="province-select"
                                        data-selected="{{ old('id_provinsi', $employee->id_provinsi) }}">
                                        <option value="">Pilih Provinsi</option>
                                        @foreach ($master['provinces'] as $province)
                                            <option value="{{ $province->id }}"
                                                {{ old('id_provinsi', $employee->id_provinsi) == $province->id ? 'selected' : '' }}>
                                                {{ $province->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <!-- KOTA -->
                            <div class="relative">
                                <label class="text-sm font-medium text-slate-700 mb-2 block">Kota
                                </label>
                                <div class="relative group">
                                    <i
                                        class="fa-solid fa-city absolute left-4 top-1/2 -translate-y-1/2 text-indigo-500 z-20 pointer-events-none transition-colors group-focus-within:text-indigo-600"></i>
                                    <select name="id_kota" class="city-select"
                                        data-selected="{{ old('id_kota', $employee->id_kota) }}">
                                        <option value="">Pilih Kota/Kabupaten</option>
                                    </select>
                                </div>
                            </div>
                            <!-- KECAMATAN -->
                            <div class="relative">
                                <label class="text-sm font-medium text-slate-700 mb-2 block">Kecamatan
                                </label>
                                <div class="relative group">
                                    <i
                                        class="fa-solid fa-map-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-blue-500 z-20 pointer-events-none transition-colors group-focus-within:text-blue-600"></i>
                                    <select name="id_kecamatan" class="district-select"
                                        data-selected="{{ old('id_kecamatan', $employee->id_kecamatan) }}">
                                        <option value="">Pilih Kecamatan</option>
                                    </select>
                                </div>
                            </div>
                            <!-- KELURAHAN -->
                            <div class="relative">
                                <label class="text-sm font-medium text-slate-700 mb-2 block">Kelurahan/Desa
                                </label>
                                <div class="relative group">
                                    <i
                                        class="fa-solid fa-house absolute left-4 top-1/2 -translate-y-1/2 text-emerald-500 z-20 pointer-events-none transition-colors group-focus-within:text-emerald-600"></i>
                                    <select name="id_kelurahan" class="village-select"
                                        data-selected="{{ old('id_kelurahan', $employee->id_kelurahan) }}">
                                        <option value="">Pilih Kelurahan/Desa</option>
                                    </select>
                                </div>
                            </div>
                            <!-- Alamat -->
                            <div class="md:col-span-2">
                                <label class="text-sm font-medium text-slate-700 mb-2 block">Alamat </label>
                                <div class="relative">
                                    <i class="fa-solid fa-map absolute left-4 top-4 text-slate-400"></i>
                                    <textarea name="alamat" maxlength="255" autocomplete="off"
                                        class="w-full min-h-12 pl-11 pr-4 pt-3 pb-3 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition resize-none">{{ trim(old('alamat', $employee->alamat)) }}</textarea>
                                </div>
                                @error('alamat')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <!-- ===================== KEPEGAWAIAN ===================== -->
                    <div class="mb-8">
                        <div class="flex items-center gap-3 mb-5">
                            <div
                                class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                <i class="fa-solid fa-briefcase"></i>
                            </div>
                            <h3 class="font-bold text-slate-800 text-lg">
                                Data Kepegawaian
                            </h3>
                        </div>
                        <div class="grid md:grid-cols-2 gap-5">
                            {{-- Status Pegawai --}}
                            <div>
                                <label for="status_pegawai" class="text-sm font-medium text-slate-700 mb-2 block">
                                    Status Pegawai
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-id-badge absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                                    <select name="status_pegawai" id="status_pegawai"
                                        class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl
                                           focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100
                                           outline-none appearance-none">
                                        <option value="">
                                            Pilih Status Pegawai
                                        </option>
                                        @foreach (['PNS', 'PPPK'] as $status)
                                            <option value="{{ $status }}" @selected(old('status_pegawai', $employee->status_pegawai) === $status)>
                                                {{ $status }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('status_pegawai')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            {{-- PNS --}}
                            <div class="relative">
                                <label for="id_pangkat" class="text-sm font-medium text-slate-700 mb-2 block">
                                    Pangkat/Golongan
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-medal absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                                    <select name="id_pangkat" id="id_pangkat"
                                        data-selected="{{ old('id_pangkat', $employee->id_pangkat) }}" disabled
                                        class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl
                                           focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100
                                           outline-none appearance-none disabled:opacity-60 disabled:cursor-not-allowed">
                                        <option value="">
                                            Memuat Pangkat/Golongan...
                                        </option>
                                    </select>
                                </div>
                                @error('id_pangkat')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            {{-- Golongan P3K --}}
                            <div class="relative">
                                <label for="kode_pppk" class="text-sm font-medium text-slate-700 mb-2 block">
                                    Golongan PPPK
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-layer-group absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                                    <select name="kode_pppk" id="kode_pppk"
                                        data-selected="{{ old('kode_pppk', $employee->kode_pppk) }}" disabled
                                        class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl
                                           focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100
                                           outline-none appearance-none disabled:opacity-60 disabled:cursor-not-allowed">
                                        <option value="">
                                            Memuat Golongan PPPK...
                                        </option>
                                    </select>
                                </div>
                                @error('kode_pppk')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            {{-- Unit Kerja --}}
                            <div>
                                <label class="text-sm font-medium text-slate-700 mb-2 block">
                                    Unit Kerja <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-building absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                                    <select name="kode_unit_kerja"
                                        class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none">
                                        <option value="">Pilih Unit Kerja</option>
                                        @foreach ($master['departments'] as $department)
                                            <option value="{{ $department->kode }}"
                                                {{ old('kode_unit_kerja', $employee->kode_unit_kerja) == $department->kode ? 'selected' : '' }}>
                                                {{ $department->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('kode_unit_kerja')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <!-- Jabatan -->
                            <div>
                                <label class="text-sm font-medium text-slate-700 mb-2 block">
                                    Jabatan <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-user-tie absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                                    <select name="kode_jabatan"
                                        class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none">
                                        <option value="">Pilih Jabatan</option>
                                        @foreach ($master['positions'] as $position)
                                            <option value="{{ $position->kode }}"
                                                {{ old('kode_jabatan', $employee->kode_jabatan) == $position->kode ? 'selected' : '' }}>
                                                {{ $position->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('kode_jabatan')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <div class="relative">
                                <label for="status" class="block mb-2 text-sm font-medium text-slate-700">
                                    Status Akun Pegawai <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-user-check absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <select id="status" name="status"
                                        class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none">
                                        <option value="">Pilih Status Akun Pegawai</option>
                                        <option value="Aktif"
                                            {{ old('status', $employee->status) == 'Aktif' ? 'selected' : '' }}>
                                            Aktif
                                        </option>
                                        <option value="Nonaktif"
                                            {{ old('status', $employee->status) == 'Nonaktif' ? 'selected' : '' }}>
                                            Nonaktif
                                        </option>
                                        <option value="Pensiun"
                                            {{ old('status', $employee->status) == 'Pensiun' ? 'selected' : '' }}>
                                            Pensiun
                                        </option>
                                    </select>
                                </div>
                                @error('status')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="text-sm font-medium text-slate-700 mb-2 block">
                                    Tanggal Masuk <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-calendar-check absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="date" name="tanggal_masuk"
                                        value="{{ old('tanggal_masuk', optional($employee->tanggal_masuk)->format('Y-m-d')) }}"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                   focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100
                                   outline-none transition">
                                </div>
                                @error('tanggal_masuk')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-slate-700">
                                    TMT Pensiun
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-person-cane absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    </i>
                                    <input type="date" name="tmt_pensiun" readonly
                                        value="{{ old('tmt_pensiun', optional($employee->tmt_pensiun)->format('Y-m-d')) }}"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                             focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none">
                                </div>
                                @error('tmt_pensiun')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <!-- TMT Pangkat Lama -->
                            <div>
                                <label for="tmt_pangkat_lama" class="block mb-2 text-sm font-medium text-slate-700">
                                    TMT Pangkat Lama
                                    <span id="tmt_pangkat_" class="text-red-500">*</span>
                                </label>
                                <div id="tmt_pangkat_lama_wrapper" class="relative">
                                    <!-- Ikon aktif -->
                                    <i id="tmt_pangkat_lama_icon"
                                        class="fa-solid fa-calendar-check absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                    <!-- Ikon nonaktif -->
                                    <i id="tmt_pangkat_lama_disabled_icon"
                                        class="fa-solid fa-ban absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none hidden"></i>
                                    <input type="date" id="tmt_pangkat_lama" name="tmt_pangkat_lama"
                                        value="{{ old('tmt_pangkat_lama', optional($employee->tmt_pangkat_lama)->format('Y-m-d')) }}"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                           focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100
                           outline-none transition">
                                </div>
                                <p id="tmt_pangkat_lama_disabled_text" class="hidden mt-2 text-xs text-slate-400">
                                    <i class="fa-solid fa-ban mr-1"></i>
                                    TMT Pangkat tidak berlaku untuk PPPK.
                                </p>
                                @error('tmt_pangkat_lama')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <!-- TMT Pangkat Berikutnya -->
                            <div>
                                <label for="tmt_pangkat_baru" class="block mb-2 text-sm font-medium text-slate-700">
                                    TMT Pangkat Berikutnya
                                </label>
                                <div id="tmt_pangkat_baru_wrapper" class="relative">
                                    <!-- Ikon aktif -->
                                    <i id="tmt_pangkat_baru_icon"
                                        class="fa-solid fa-arrow-trend-up absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                    <!-- Ikon nonaktif -->
                                    <i id="tmt_pangkat_baru_disabled_icon"
                                        class="fa-solid fa-ban absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none hidden"></i>
                                    <input type="date" id="tmt_pangkat_baru" name="tmt_pangkat_baru" readonly
                                        value="{{ old('tmt_pangkat_baru', optional($employee->tmt_pangkat_baru)->format('Y-m-d')) }}"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                           focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100
                                           outline-none transition">
                                </div>
                                <p id="tmt_pangkat_baru_disabled_text" class="hidden mt-2 text-xs text-slate-400">
                                    <i class="fa-solid fa-ban mr-1"></i>
                                    TMT Pangkat Berikutnya tidak berlaku untuk PPPK.
                                </p>
                                @error('tmt_pangkat_baru')
                                    <p class="mt-2 text-sm text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <!-- TMT KGB Lama-->
                            <div>
                                <label for="tmt_kgb_lama" class="block mb-2 text-sm font-medium text-slate-700">
                                    TMT KGB <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-money-check-dollar absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="date" id="tmt_kgb_lama" name="tmt_kgb_lama"
                                        value="{{ old('tmt_kgb_lama', optional($employee->tmt_kgb_lama)->format('Y-m-d')) }}"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                            focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
                                </div>
                                @error('tmt_kgb_lama')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <!-- TMT KGB Baru -->
                            <div>
                                <label class="block mb-2 text-sm font-medium text-slate-700">
                                    TMT KGB Berikutnya
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-money-check-dollar absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    </i>
                                    <input type="date" name="tmt_kgb_baru" readonly
                                        value="{{ old('tmt_kgb_baru', optional($employee->tmt_kgb_baru)->format('Y-m-d')) }}"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                             focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none">
                                </div>
                                @error('tmt_kgb_baru')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- Masa Kerja Tahun --}}
                            <div>
                                <label class="block mb-2 text-sm font-medium text-slate-700">
                                    Masa Kerja (Tahun) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-calendar-days absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="number" name="tahun_masa_kerja"
                                        value="{{ old('tahun_masa_kerja', $employee->tahun_masa_kerja) }}"
                                        placeholder="Masukkan tahun" min="0" inputmode="numeric"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                       focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
                                </div>
                                @error('tahun_masa_kerja')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- Masa Kerja Bulan --}}
                            <div>
                                <label class="block mb-2 text-sm font-medium text-slate-700">
                                    Masa Kerja (Bulan) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-calendar-days absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="number" name="bulan_masa_kerja"
                                        value="{{ old('bulan_masa_kerja', $employee->bulan_masa_kerja) }}"
                                        placeholder="0 - 11" min="0" max="11" inputmode="numeric"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                                 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
                                </div>
                                @error('bulan_masa_kerja')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <input type="hidden" name="employee_id" value="{{ $employee->nip }}">
                    </div>
                    <div class="md:col-span-2 mb-4">
                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-slate-800">
                                Dokumen Kepegawaian
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Dokumen bersifat opsional. Pilih file baru hanya jika ingin
                                mengganti dokumen yang sudah tersimpan.
                            </p>
                        </div>
                        @php
                            $jenisDokumen = [
                                'SK_KGB' => 'SK Kenaikan Gaji Berkala (KGB)',
                                'SK_PANGKAT' => 'SK Pangkat Terakhir',
                                'SK_JABATAN' => 'SK Jabatan Terakhir',
                                'IJAZAH_TERAKHIR' => 'Ijazah Terakhir',
                                'SK_PENGANGKATAN_PNS' => 'SK Pengangkatan PNS',
                                'SK_PENGANGKATAN_PPPK' => 'SK Pengangkatan PPPK',
                                'KTP' => 'Kartu Tanda Penduduk (KTP)',
                            ];
                            $dokumenTersimpan = $employee->dokumen->keyBy('jenis_dokumen');
                        @endphp
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            @foreach ($jenisDokumen as $jenis => $label)
                                @php
                                    $dokumenSaatIni = $dokumenTersimpan->get($jenis);
                                    $fileDokumenTersedia = $dokumenSaatIni
                                        && filled($dokumenSaatIni->nama_file)
                                        && \Illuminate\Support\Facades\Storage::disk('local')
                                            ->exists($dokumenSaatIni->nama_file);
                                @endphp
                                <div
                                    class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-blue-300">
                                    <label for="dokumen_{{ $jenis }}"
                                        class="mb-2 block text-sm font-medium text-slate-700">
                                        {{ $label }}
                                    </label>
                                    <div id="infoDokumen_{{ $jenis }}"
                                        class="mb-3 flex items-start gap-3 rounded-xl bg-slate-50 p-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                            <i class="fa-solid fa-file-lines text-lg"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            @if ($fileDokumenTersedia)
                                                <p class="text-xs font-medium text-emerald-700">
                                                    <i class="fa-solid fa-circle-check mr-1"></i>
                                                    Dokumen sudah tersedia
                                                </p>
                                                <p class="mt-1 break-all text-sm text-slate-700">
                                                    {{ $dokumenSaatIni->nama_file_asli }}
                                                </p>
                                            @elseif ($dokumenSaatIni)
                                                <p class="text-xs font-medium text-amber-700">
                                                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                                                    Metadata tersedia, file tidak ditemukan.
                                                </p>
                                                <p class="mt-1 break-all text-sm text-slate-700">
                                                    {{ $dokumenSaatIni->nama_file_asli }}
                                                </p>
                                            @else
                                                <p class="text-sm font-medium text-slate-600">
                                                    Belum ada dokumen
                                                </p>
                                                <p class="mt-1 text-xs text-slate-400">
                                                    Dokumen ini belum diunggah.
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <label for="dokumen_{{ $jenis }}"
                                        class="flex cursor-pointer items-center gap-3 rounded-xl border-2 border-dashed border-slate-300 bg-white p-4 transition hover:border-blue-400 hover:bg-blue-50">
                                        <i class="fa-solid fa-cloud-arrow-up text-lg text-blue-600"></i>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-slate-700">
                                                {{ $dokumenSaatIni ? 'Ganti dokumen' : 'Upload dokumen' }}
                                            </p>
                                            <p id="namaFile_{{ $jenis }}"
                                                class="mt-1 break-all text-xs text-slate-500">
                                                Belum ada file terpilih.
                                            </p>
                                        </div>
                                    </label>
                                    <input type="file" id="dokumen_{{ $jenis }}"
                                        name="dokumen[{{ $jenis }}]"
                                        accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                        class="document-upload-input hidden"
                                        data-filename="#namaFile_{{ $jenis }}"
                                        data-info="#infoDokumen_{{ $jenis }}"
                                        data-status="#statusDokumen_{{ $jenis }}" data-edit="true"
                                        data-existing="{{ $fileDokumenTersedia ? 'true' : 'false' }}">
                                    <p id="statusDokumen_{{ $jenis }}"
                                        class="mt-2 text-xs {{ $fileDokumenTersedia ? 'text-emerald-700' : ($dokumenSaatIni ? 'text-amber-700' : 'text-slate-500') }}"
                                        aria-live="polite">
                                        {{ $fileDokumenTersedia ? 'Dokumen telah diunggah.' : ($dokumenSaatIni ? 'File dokumen tidak ditemukan. Pilih file untuk mengganti.' : 'Dokumen ini belum diunggah.') }}
                                    </p>
                                    @error("dokumen.$jenis")
                                        <p class="mt-2 text-sm text-red-500">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="border-t border-slate-200 bg-slate-50 px-8 py-6">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                            {{-- INFORMASI --}}
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center shadow-sm">
                                    <i class="fa-solid fa-circle-info text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-800">
                                        Konfirmasi Perubahan Data
                                    </h4>
                                    <p class="text-sm text-slate-500 mt-1 leading-relaxed">
                                        Pastikan seluruh data pegawai telah sesuai sebelum disimpan.
                                        Perubahan yang telah disimpan akan langsung memperbarui data
                                        pada sistem.
                                    </p>
                                </div>
                            </div>
                            <!-- Tombol -->
                            <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                                {{-- KEMBALI --}}
                                <a href="{{ route('employee.index') }}"
                                    class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl border border-slate-300 bg-white text-slate-700 font-medium
                                         hover:bg-slate-100 hover:border-slate-400 hover:text-slate-800 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-slate-100">
                                    <i class="fa-solid fa-arrow-left"></i>
                                    <span>Kembali</span>
                                </a>
                                {{-- SIMPAN --}}
                                <button type="submit"
                                    class="inline-flex items-center justify-center gap-2 px-7 py-3 rounded-2xl bg-blue-600 text-white font-semibold shadow-lg shadow-blue-200 hover:bg-blue-700 hover:shadow-xl transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-blue-100 active:scale-[0.98]">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    <span>Simpan Perubahan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
