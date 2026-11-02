<div id="modalTambahPegawai" role="dialog" aria-modal="true"
    class="modal fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <!-- CARD -->
    <div
        class="bg-white w-full max-w-7xl h-[95vh] rounded-[32px] shadow-[0_20px_60px_rgba(0,0,0,0.15)] overflow-hidden animate-[modalShow_.25s_ease] flex flex-col">
        <!-- HEADER -->
        <div class="relative bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 px-8 py-7">
            <div class="absolute right-0 top-0 opacity-10">
                <i class="fa-solid fa-user-plus text-[180px]"></i>
            </div>
            <div class="relative flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center text-white text-2xl">
                        <i class="fa-solid fa-id-card"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">
                            Tambah Pegawai
                        </h2>
                        <p class="text-blue-100">
                            Lengkapi informasi pegawai baru
                        </p>
                    </div>
                </div>
                <button onclick="closeAllModal()"
                    class="w-11 h-11 rounded-xl bg-white/20 hover:bg-white/30 text-white transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <!-- BODY -->
        <form id="pegawaiForm" enctype="multipart/form-data" method="POST" action="{{ route('employee.store') }}"
            class="flex-1 flex flex-col min-h-0">
            @csrf
            @if ($errors->any() && session('open_create_modal'))
                <div id="pegawaiFormErrors"
                    class="mx-6 mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700" role="alert">
                    <p class="mb-2 font-semibold">
                        Data pegawai belum berhasil disimpan.
                    </p>
                    <ul class="list-disc space-y-1 pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="flex-1 min-h-0 p-6 md:p-8 overflow-y-auto sidebar-scroll">
                <!-- ===================== INFORMASI PRIBADI ===================== -->
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <h3 class="font-bold text-slate-800 text-lg">Informasi Pribadi</h3>
                    </div>
                    <div class="grid md:grid-cols-2 gap-5">
                        <!-- NIK -->
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                NIK
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-id-card absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" placeholder="Masukkan NIK" name="nik" maxlength="16" autofocus
                                    autocomplete="off" minlength="16" pattern="[0-9]{16}" value="{{ old('nik') }}"
                                    class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
                            </div>
                            @error('nik')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- Nama -->
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-2 block">Nama Lengkap <span
                                    class="text-red-500">*</span></label>
                            <div class="relative">
                                <i class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" placeholder="Masukkan Nama Lengkap" name="nama" maxlength="100"
                                    autocomplete="off" minlength="1" value="{{ old('nama') }}"
                                    class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
                            </div>
                            @error('nama')
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
                                        <img id="previewTambah" src="{{ asset('images/default-user.png') }}"
                                            class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg">
                                        <label for="fotoTambah"
                                            class="absolute bottom-0 right-0 w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-lg cursor-pointer hover:bg-blue-700 transition">
                                            <i class="fa-solid fa-camera"></i>
                                        </label>
                                    </div>
                                    <h4 class="mt-5 font-semibold text-slate-700">
                                        Upload Foto Pegawai
                                    </h4>
                                    <p id="namaFileTambah" class="text-sm text-slate-500 text-center mt-1">
                                        Klik ikon kamera untuk memilih foto<br>
                                        Format JPG, JPEG, PNG • Maksimal 2 MB
                                    </p>
                                    <input type="file" id="fotoTambah" name="foto"
                                        accept="image/jpeg,image/jpg,image/png" class="hidden image-preview-input"
                                        data-preview="#previewTambah" data-filename="#namaFileTambah">
                                </div>
                            </div>
                            @error('foto')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- TEMPAT LAHIR -->
                        <div class="relative">
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                Tempat Lahir <span class="text-red-500">*</span>
                            </label>
                            <div class="relative group">
                                <i
                                    class="fa-solid fa-location-dot z-20 absolute left-4 top-1/2 -translate-y-1/2 text-blue-500 transition-colors group-focus-within:text-blue-600"></i>
                                <select name="tempat_lahir" id="tempat_lahir" class="tempat-lahir-select"
                                    data-selected="{{ old('tempat_lahir') }}">
                                    <option value="">Pilih Tempat Lahir</option>
                                    @foreach ($master['cities'] as $item)
                                        <option value="{{ $item->id }}"
                                            {{ old('tempat_lahir') == $item->id ? 'selected' : '' }}>
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
                                <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}"
                                    autocomplete="off"
                                    class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
                            </div>
                            @error('tanggal_lahir')
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
                                    value="{{ old('nip') }}" required
                                    class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
                            </div>
                            @error('nip')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- Jenis Kelamin -->
                        <div class="relative">
                            <label class="text-sm font-medium text-slate-700 mb-2 block">Jenis Kelamin <span
                                    class="text-red-500">*</span></label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-venus-mars absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <select name="jenis_kelamin"
                                    class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none">
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="Laki-laki"
                                        {{ old('jenis_kelamin') == 'Laki-laki' ? 'selected' : '' }}>
                                        Laki-laki
                                    </option>
                                    <option value="Perempuan"
                                        {{ old('jenis_kelamin') == 'Perempuan' ? 'selected' : '' }}>
                                        Perempuan
                                    </option>
                                </select>
                            </div>
                            @error('jenis_kelamin')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <div class="relative">
                            <label class="text-sm font-medium text-slate-700 mb-2 block">Agama</label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-mosque absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <select name="agama" value="{{ old('agama') }}"
                                    class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none">
                                    <option value="">Pilih Agama</option>
                                    @foreach (['Islam', 'Kristen Protestan', 'Kristen Katolik', 'Budha', 'Hindu', 'Konghucu'] as $agama)
                                        <option value="{{ $agama }}"
                                            {{ old('agama') == $agama ? 'selected' : '' }}>
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
                        <div class="relative">
                            <label class="text-sm font-medium text-slate-700 mb-2 block">Status Pernikahan</label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-heart absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <select name="status_nikah" value="{{ old('status_nikah') }}"
                                    class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none">
                                    <option value="">Pilih Status Pernikahan</option>
                                    @foreach (['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'] as $status)
                                        <option value="{{ $status }}"
                                            {{ old('status_nikah') == $status ? 'selected' : '' }}>
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
                        <!-- HP -->
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-2 block">No. HP <span
                                    class="text-red-500">*</span></label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="tel" name="no_hp" id="no_hp" placeholder="08xxxxxxxxxx"
                                    maxlength="16" minlength="10" pattern="08[0-9]{8,14}"
                                    value="{{ old('no_hp') }}" autocomplete="off" inputmode="numeric"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                    class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl" />
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
                                    maxlength="100" value="{{ old('email') }}" autocomplete="off"
                                    class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl">
                            </div>
                            @error('email')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <div class="relative">
                            <label for="tingkat_pendidikan" class="text-sm font-medium text-slate-700 mb-2 block">
                                Pendidikan
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-graduation-cap absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                                <select name="tingkat_pendidikan" id="tingkat_pendidikan"
                                    data-ranks-url="{{ route('employee.ranks-by-education', ':kode') }}"
                                    class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none">
                                    <option value="">
                                        Pilih Tingkat Pendidikan
                                    </option>
                                    @foreach ($master['educationLevels'] as $education)
                                        <option value="{{ $education->kode }}"
                                            data-pangkat-pns-minimum="{{ $education->pangkat_pns_minimum }}"
                                            data-pangkat-pns-maksimum="{{ $education->pangkat_pns_maksimum }}"
                                            data-golongan-pppk-minimum="{{ $education->golongan_pppk_minimum }}"
                                            data-golongan-pppk-maksimum="{{ $education->golongan_pppk_maksimum }}"
                                            @selected(old('tingkat_pendidikan') === $education->kode)>
                                            {{ $education->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('tingkat_pendidikan')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- PROVINSI -->
                        <div class="relative">
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                Provinsi
                            </label>
                            <div class="relative group">
                                <i
                                    class="fa-solid fa-earth-asia absolute left-4 top-1/2 -translate-y-1/2 text-blue-500 z-20 pointer-events-none transition-colors group-focus-within:text-blue-600">
                                </i>
                                <select id="province" name="id_provinsi" class="province-select"
                                    data-selected="{{ old('id_provinsi') }}">
                                    <option value="">Pilih Provinsi</option>
                                    @foreach ($master['provinces'] as $province)
                                        <option value="{{ $province->id }}"
                                            {{ old('id_provinsi') == $province->id ? 'selected' : '' }}>
                                            {{ $province->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('id_provinsi')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- KOTA -->
                        <div class="relative">
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                Kota
                            </label>
                            <div class="relative group">
                                <i
                                    class="fa-solid fa-city absolute left-4 top-1/2 -translate-y-1/2 text-indigo-500 z-20 pointer-events-none transition-colors group-focus-within:text-indigo-600">
                                </i>
                                <select id="city" name="id_kota" class="city-select"
                                    data-selected="{{ old('id_kota') }}">
                                    <option value="">Pilih Kota/Kabupaten</option>
                                </select>
                            </div>
                            @error('id_kota')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- KECAMATAN -->
                        <div class="relative">
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                Kecamatan
                            </label>
                            <div class="relative group">
                                <i
                                    class="fa-solid fa-map-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-blue-500 z-20 pointer-events-none transition-colors group-focus-within:text-blue-600">
                                </i>
                                <select id="district" name="id_kecamatan" class="district-select"
                                    data-selected="{{ old('id_kecamatan') }}">
                                    <option value="">Pilih Kecamatan</option>
                                </select>
                            </div>
                            @error('id_kecamatan')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- KELURAHAN -->
                        <div class="relative">
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                Kelurahan/Desa
                            </label>
                            <div class="relative group">
                                <i
                                    class="fa-solid fa-house absolute left-4 top-1/2 -translate-y-1/2 text-emerald-500 z-20 pointer-events-none transition-colors group-focus-within:text-emerald-600">
                                </i>
                                <select id="urbanVillage" name="id_kelurahan" class="village-select"
                                    data-selected="{{ old('id_kelurahan') }}">
                                    <option value="">Pilih Kelurahan/Desa</option>
                                </select>
                            </div>
                            @error('id_kelurahan')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- Alamat -->
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-slate-700 mb-2 block">Alamat</label>
                            <div class="relative">
                                <i class="fa-solid fa-map absolute left-4 top-4 text-slate-400"></i>
                                <textarea rows="3" placeholder="Masukkan Alamat Lengkap" name="alamat" maxlength="255" autocomplete="off"
                                    class="w-full min-h-12 pl-11 pr-4 pt-3 pb-3 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition resize-none">{{ old('alamat') }}</textarea>
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
                        <h3 class="font-bold text-slate-800 text-lg">Data Kepegawaian</h3>
                    </div>
                    <div class="grid md:grid-cols-2 gap-5">
                        {{-- ============================================================
                        | STATUS PEGAWAI
                        ============================================================ --}}
                        <div>
                            <input type="hidden" name="status" value="Aktif">
                            <label for="status_pegawai" class="text-sm font-medium text-slate-700 mb-2 block">
                                Status Pegawai
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-id-badge absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <select name="status_pegawai" id="status_pegawai"
                                    class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none">
                                    <option value="">
                                        Pilih Status Pegawai
                                    </option>
                                    @foreach (['PNS', 'PPPK'] as $status)
                                        <option value="{{ $status }}" @selected(old('status_pegawai') === $status)>
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
                        {{-- Pangat PNS --}}
                        <div class="relative">
                            <label for="id_pangkat" class="text-sm font-medium text-slate-700 mb-2 block">
                                Pangkat/Golongan
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-medal absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                                <select name="id_pangkat" id="id_pangkat"
                                    data-selected="{{ old('id_pangkat', '') }}"  disabled
                                    class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none disabled:opacity-60 disabled:cursor-not-allowed">
                                    <option value="">
                                        Pilih pendidikan dan status pegawai terlebih dahulu
                                    </option>
                                </select>
                            </div>
                            @error('id_pangkat')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        {{-- Golongan PPPK --}}
                        <div class="relative">
                            <label for="kode_pppk" class="text-sm font-medium text-slate-700 mb-2 block">
                                Golongan PPPK
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-layer-group absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                                <select name="kode_pppk" id="kode_pppk" data-selected="{{ old('kode_pppk', '') }}"
                                     disabled
                                    class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none disabled:opacity-60 disabled:cursor-not-allowed">
                                    <option value="">
                                        Pilih pendidikan dan status pegawai terlebih dahulu
                                    </option>
                                </select>
                            </div>
                            @error('kode_pppk')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
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
                                            {{ old('kode_unit_kerja') == $department->kode ? 'selected' : '' }}>
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
                                            {{ old('kode_jabatan') == $position->kode ? 'selected' : '' }}>
                                            {{ $position->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('kode_jabatan')
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
                                <input type="date" name="tanggal_masuk" value="{{ old('tanggal_masuk') }}"
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
                        <!-- TMT Pangkat -->
                        <div>
                            <label for="tmt_pangkat_lama" class="block mb-2 text-sm font-medium text-slate-700">
                                TMT Pangkat
                                <span id="tmt_pangkat_required" class="text-red-500">*</span>
                            </label>
                            <div id="tmt_pangkat_lama_wrapper" class="relative">
                                <i id="tmt_pangkat_lama_icon"
                                    class="fa-solid fa-calendar-check absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                <i id="tmt_pangkat_lama_disabled_icon"
                                    class="fa-solid fa-ban absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none hidden"></i>
                                <input type="date" id="tmt_pangkat_lama" name="tmt_pangkat_lama"
                                    value="{{ old('tmt_pangkat_lama') }}"
                                    class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                       focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
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
                        <!-- TMT KGB-->
                        <div>
                            <label for="tmt_kgb_lama" class="block mb-2 text-sm font-medium text-slate-700">
                                TMT KGB <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-money-check-dollar absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="date" id="tmt_kgb_lama" name="tmt_kgb_lama"
                                    value="{{ old('tmt_kgb_lama') }}"
                                    class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                            focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
                            </div>
                            @error('tmt_kgb_lama')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Masa Kerja Tahun --}}
                            <div>
                                <label for="tahun_masa_kerja" class="block mb-2 text-sm font-medium text-slate-700">
                                    Masa Kerja (Tahun) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-calendar-days absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="number" id="tahun_masa_kerja" name="tahun_masa_kerja"
                                        value="{{ old('tahun_masa_kerja', 0) }}" placeholder="Masukkan tahun"
                                        min="0" step="1" inputmode="numeric"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
                                </div>
                                @error('tahun_masa_kerja')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- Masa Kerja Bulan --}}
                            <div>
                                <label for="bulan_masa_kerja" class="block mb-2 text-sm font-medium text-slate-700">
                                    Masa Kerja (Bulan) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-calendar-days absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="number" id="bulan_masa_kerja" name="bulan_masa_kerja"
                                        value="{{ old('bulan_masa_kerja', 0) }}" placeholder="0 - 11" min="0"
                                        max="11" step="1" inputmode="numeric"
                                        class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition">
                                </div>
                                @error('bulan_masa_kerja')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <div class="mb-4">
                        <h3 class="text-base font-semibold text-slate-800">
                            Dokumen Kepegawaian
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Upload dokumen pendukung pegawai. Seluruh dokumen bersifat opsional.
                            Format PDF, JPG, JPEG, atau PNG dengan ukuran maksimal 5 MB per file.
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
                    @endphp
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach ($jenisDokumen as $jenis => $label)
                            <div
                                class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-blue-300">
                                <label for="dokumen_{{ $jenis }}"
                                    class="mb-2 block text-sm font-medium text-slate-700">
                                    {{ $label }}
                                </label>
                                <label for="dokumen_{{ $jenis }}"
                                    class="flex cursor-pointer items-center gap-3 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-4 transition hover:border-blue-400 hover:bg-blue-50">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                                        <i class="fa-solid fa-file-arrow-up text-lg"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-slate-700">
                                            Pilih dokumen
                                        </p>
                                        <p id="namaFile_{{ $jenis }}"
                                            class="mt-1 break-all text-xs text-slate-500">
                                            Belum ada file terpilih.
                                        </p>
                                    </div>
                                    <i class="fa-solid fa-folder-open text-slate-400"></i>
                                </label>
                                <input type="file" id="dokumen_{{ $jenis }}"
                                    name="dokumen[{{ $jenis }}]"
                                    accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                    class="document-upload-input hidden"
                                    data-filename="#namaFile_{{ $jenis }}"
                                    data-status="#statusDokumen_{{ $jenis }}" data-edit="false"
                                    data-existing="false">
                                <p id="statusDokumen_{{ $jenis }}" class="mt-2 text-xs text-slate-500"
                                    aria-live="polite">
                                    Dokumen ini belum diunggah.
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
            </div>
        </form>
        <!-- FOOTER -->
        <div
            class="px-8 py-5 border-t border-slate-100 bg-slate-50 flex flex-col sm:flex-row gap-3 w-full sm:w-auto justify-between items-center">
            <p class="text-sm text-slate-500">
                Pastikan data yang dimasukkan sudah benar.
            </p>
            <div class="flex gap-3">
                <button onclick="closeAllModal()" type="button" aria-label="Tutup Modal"
                    class="px-6 py-3 rounded-2xl border border-slate-200 hover:bg-white transition">
                    Batal
                </button>
                <button type="submit" form="pegawaiForm"
                    class="px-6 py-3 rounded-2xl bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-200 transition">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>
                    Simpan Data
                </button>
            </div>
        </div>
    </div>
</div>
