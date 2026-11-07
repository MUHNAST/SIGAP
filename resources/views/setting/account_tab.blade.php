<div id="account-tab" class="p-6">
    <!-- FOTO PROFIL -->
    <div class="border border-slate-200 rounded-2xl p-6 mb-4">
        <h3 class="text-xl font-semibold mb-2">
            Foto Profil
        </h3>
        <p class="text-slate-500 text-sm mb-6">
            Gunakan foto dengan format JPG, JPEG, atau PNG. Maksimal ukuran 2 MB.
        </p>
        <div class="flex flex-col lg:flex-row gap-8 items-center">
            <!-- Preview -->
            <div class="flex flex-col items-center">
                <div class="relative group">
                    <img id="previewProfile"
                        src="{{ !$user->foto || $user->foto === 'images/default-user.png'
                            ? asset('images/default-user.png')
                            : asset('storage/' . $user->foto) }}"
                        class="w-44 h-44 rounded-full object-cover border-4 border-slate-200 shadow-lg">
                    <label for="inputFotoProfile"
                        class="absolute inset-0 rounded-full bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center cursor-pointer">
                        <i class="fa-solid fa-camera text-white text-3xl"></i>
                    </label>
                </div>
                <p class="mt-4 text-center">
                    <span class="font-semibold block">
                        {{ $user->nama }}
                    </span>
                    <span class="text-slate-500 text-sm">
                        {{ ucfirst($user->role) }}
                    </span>
                </p>
            </div>
            <!-- Form -->
            <div class="flex-1 w-full">
                <form action="{{ route('setting.photo.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="active_tab" class="active-tab-input">
                    <input type="file" id="inputFotoProfile" name="foto" accept="image/jpeg,image/jpg,image/png"
                        class="hidden image-preview-input" data-preview="#previewProfile"
                        data-filename="#namaFileProfile">
                    <div class="bg-slate-50 border border-dashed border-slate-300 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fa-solid fa-circle-info text-blue-600"></i>
                            <span class="font-medium">
                                Informasi Foto
                            </span>
                        </div>
                        <ul class="text-sm text-slate-500 space-y-1 list-disc ml-5">
                            <li>Format: JPG, JPEG, PNG</li>
                            <li>Ukuran maksimal 2 MB</li>
                            <li>Gunakan foto yang jelas dan menghadap ke depan</li>
                        </ul>
                    </div>
                    <p id="namaFileProfile" class="mt-4 text-sm text-slate-500 italic">
                        Klik ikon kamera untuk memilih foto<br>
                        Format JPG, JPEG, PNG • Maksimal 2 MB
                    </p>
                    @error('foto')
                        <p class="mt-2 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                    <div class="flex flex-wrap gap-3 my-6">
                        <label for="inputFotoProfile"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl cursor-pointer transition">
                            <i class="fa-solid fa-image"></i>
                            Pilih Foto
                        </label>
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Simpan
                        </button>
                        {{-- <button type="button" onclick="openModal('modalResetFoto')"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl transition">
                            <i class="fa-solid fa-trash"></i>
                            Hapus Foto
                        </button> --}}
                        <button type="button"
                            @if ($user->foto && $user->foto !== 'images/default-user.png') onclick="openModal('modalResetFoto')"
                            @else
                                disabled @endif
                            class="inline-flex items-center gap-2 px-5 py-3 border rounded-xl transition
                                {{ $user->foto && $user->foto !== 'images/default-user.png'
                                    ? 'bg-red-50 hover:bg-red-100 text-red-600 border-red-200'
                                    : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed opacity-60' }}">
                            <i class="fa-solid fa-trash"></i>
                            Hapus Foto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- INFORMASI AKUN -->
    <div class="border border-slate-200 rounded-2xl p-6 mb-6">
        <!-- Header -->
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                <i class="fa-solid fa-user-gear text-blue-600 text-lg"></i>
            </div>
            <div>
                <h3 class="text-xl font-semibold text-slate-800">
                    Informasi Akun
                </h3>
                <p class="text-slate-500 text-sm">
                    Kelola informasi akun login pengguna.
                </p>
            </div>
        </div>
        <form action="{{ route('setting.account.update') }}" method="POST" class="mt-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="active_tab" value="account">
            <div class="grid md:grid-cols-2 gap-5">
                {{-- Nama --}}
                <div>
                    <label for="nama" class="block mb-2 text-sm font-medium text-slate-700">
                        Nama
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="nama" name="nama" value="{{ old('nama', $user->nama) }}"
                            autocomplete="off"
                            class="w-full pl-12 pr-4 py-3 rounded-xl border transition
                    @error('nama') border-red-500 @else border-slate-200 @enderror">
                    </div>
                    @error('nama')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-sm text-slate-500">
                            Masukkan nama lengkap sesuai identitas pengguna
                        </p>
                    @enderror
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-slate-700">
                        Username
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-user-shield absolute left-4 top-1/2 -translate-y-1/2 text-indigo-500"></i>
                        <input type="text" readonly value="{{ ucfirst($user->username) }}" autocomplete="off"
                            class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-600">
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        Username ditentukan oleh Super Admin dan tidak dapat diubah.
                    </p>
                </div>
                {{-- Email --}}
                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-slate-700">
                        Email
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                            autocomplete="off"
                            class="w-full pl-12 pr-4 py-3 rounded-xl border transition
                    @error('email') border-red-500 @else border-slate-200 @enderror">
                    </div>
                    @error('email')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-sm text-slate-500">
                            Digunakan untuk menerima notifikasi melalui email.
                        </p>
                    @enderror
                </div>
                {{-- Nomor HP --}}
                <div>
                    <label for="phone" class="block mb-2 text-sm font-medium text-slate-700">
                        Nomor HP
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="phone" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}"
                            autocomplete="off" maxlength="14" minlength="10" pattern="08[0-9]{8,12}"
                            oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                            class="w-full pl-12 pr-4 py-3 rounded-xl border transition
                    @error('no_hp') border-red-500 @else border-slate-200 @enderror">
                    </div>
                    @error('no_hp')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-sm text-slate-500">
                            Gunakan nomor aktif yang diawali dengan 08.
                        </p>
                    @enderror
                </div>
                {{-- Role --}}
                <div>
                    <label class="block mb-2 text-sm font-medium text-slate-700">
                        Role
                    </label>
                    <div class="relative">
                        <i
                            class="fa-solid fa-user-shield absolute left-4 top-1/2 -translate-y-1/2 text-indigo-500"></i>
                        <input type="text" readonly value="{{ ucfirst($user->role) }}"
                            class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-600">
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        Role ditentukan oleh Super Admin dan tidak dapat diubah.
                    </p>
                </div>
                <!-- Unit Kerja -->
                <div>
                    <label for="unit_kerja" class="block mb-2 text-sm font-medium text-slate-700">
                        Unit Kerja
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-building absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="unit_kerja" readonly
                            value="{{ optional($user->department)->nama ?? '-' }}"
                            class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-600 font-medium cursor-not-allowed">
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        Unit kerja ditentukan oleh Super Admin dan tidak dapat diubah.
                    </p>
                </div>
            </div>
            <div class="mt-8 flex justify-end">
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
    {{-- INFORMASI DATA PEGAWAI --}}
    @if ($user->role === 'user')
        @if ($user->employee)
            @php
                $employee = $user->employee;
            @endphp
            @php
                $inputClass =
                    'w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition';
                $selectClass =
                    'w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition';
                $iconClass =
                    'absolute left-4 top-1/2 -translate-y-1/2 z-20 pointer-events-none text-slate-400 transition-colors group-focus-within:text-blue-600';
                $labelClass = 'block mb-2 text-sm font-medium text-slate-700';
            @endphp
            <div class="border border-slate-200 rounded-2xl p-5 md:p-7 mb-6 bg-white shadow-sm">
                {{-- HEADER --}}
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-11 h-11 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-id-card text-blue-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg md:text-xl font-semibold text-slate-800">
                            Informasi Data Pegawai
                        </h3>
                        <p class="text-sm text-slate-500">
                            Kelola informasi pribadi dan data kepegawaian Anda.
                        </p>
                    </div>
                </div>
                {{-- KETERANGAN WAJIB --}}
                <div
                    class="flex items-center gap-2 mb-6 text-sm text-slate-500 bg-slate-50 border border-slate-100 rounded-xl px-4 py-3">
                    <span class="text-red-500 font-semibold">*</span>
                    <span>Kolom wajib diisi.</span>
                </div>
                <form action="{{ route('setting.employee.update') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-8">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="active_tab" value="account">
                    {{-- IDENTITAS PEGAWAI --}}
                    <div>
                        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-100">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-user text-blue-600"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-semibold text-slate-800">
                                    Identitas Pegawai
                                </h4>
                                <p class="text-sm text-slate-500">
                                    Informasi identitas dan data pribadi pegawai.
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- NIP --}}
                            <div>
                                <label class="{{ $labelClass }}">
                                    NIP <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-hashtag {{ $iconClass }}"></i>
                                    <input type="text" value="{{ $employee->nip }}" readonly
                                        class="w-full h-12 pl-11 pr-4 bg-slate-100 border border-slate-200 rounded-2xl text-slate-500 cursor-not-allowed">
                                </div>
                                <p class="mt-2 text-xs text-slate-500">
                                    NIP merupakan identitas utama pegawai dan tidak dapat diubah.
                                </p>
                            </div>
                            {{-- NIK --}}
                            <div>
                                <label for="nik" class="{{ $labelClass }}">
                                    NIK
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-id-card {{ $iconClass }}"></i>
                                    <input type="text" id="nik" name="nik" required
                                        value="{{ old('nik', $employee->nik) }}" maxlength="16" inputmode="numeric"
                                        oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                        class="{{ $inputClass }} @error('nik', 'employee') border-red-500 @enderror">
                                </div>
                                @error('nik', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- NAMA LENGKAP --}}
                            <div>
                                <label for="nama_pegawai" class="{{ $labelClass }}">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-user {{ $iconClass }}"></i>
                                    <input type="text" id="nama_pegawai" name="nama"required
                                        value="{{ old('nama', $employee->nama) }}"
                                        class="{{ $inputClass }} @error('nama', 'employee') border-red-500 @enderror">
                                </div>
                                @error('nama', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- EMAIL --}}
                            <div>
                                <label for="email_pegawai" class="{{ $labelClass }}">
                                    Email Pegawai
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-envelope {{ $iconClass }}"></i>
                                    <input type="email" id="email_pegawai" name="email" required
                                        value="{{ old('email', $employee->email) }}"
                                        class="{{ $inputClass }} @error('email', 'employee') border-red-500 @enderror">
                                </div>
                                @error('email', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- NOMOR HP --}}
                            <div>
                                <label for="no_hp_pegawai" class="{{ $labelClass }}">
                                    Nomor HP <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-phone {{ $iconClass }}"></i>
                                    <input type="tel" id="no_hp_pegawai" name="no_hp" required
                                        value="{{ old('no_hp', $employee->no_hp) }}" maxlength="14" minlength="10"
                                        pattern="08[0-9]{8,12}" inputmode="numeric"
                                        oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                        class="{{ $inputClass }} @error('no_hp', 'employee') border-red-500 @enderror">
                                </div>
                                @error('no_hp', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- TEMPAT LAHIR --}}
                            <div class="relative">
                                <label for="tempat_lahir" class="text-sm font-medium text-slate-700 mb-2 block">
                                    Tempat Lahir
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative group">
                                    <i
                                        class="fa-solid fa-location-dot z-20 absolute left-4 top-1/2 -translate-y-1/2 text-blue-500 pointer-events-none transition-colors group-focus-within:text-blue-600"></i>
                                    <select name="tempat_lahir" id="tempat_lahir" class="tempat-lahir-select"
                                        required data-selected="{{ old('tempat_lahir', $employee->tempat_lahir) }}">
                                        <option value="">Pilih Tempat Lahir</option>
                                        @foreach ($master['cities'] as $city)
                                            <option value="{{ $city->id }}" @selected(old('tempat_lahir', $employee->tempat_lahir) == $city->id)>
                                                {{ $city->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('tempat_lahir', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- TANGGAL LAHIR --}}
                            <div>
                                <label for="tanggal_lahir" class="{{ $labelClass }}">
                                    Tanggal Lahir <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-calendar-days {{ $iconClass }}"></i>
                                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" required
                                        value="{{ old('tanggal_lahir', $employee->tanggal_lahir ? \Illuminate\Support\Carbon::parse($employee->tanggal_lahir)->format('Y-m-d') : '') }}"
                                        class="{{ $inputClass }} @error('tanggal_lahir', 'employee') border-red-500 @enderror">
                                </div>
                                @error('tanggal_lahir', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- JENIS KELAMIN --}}
                            <div>
                                <label for="jenis_kelamin" class="{{ $labelClass }}">
                                    Jenis Kelamin <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-venus-mars {{ $iconClass }}"></i>
                                    <select id="jenis_kelamin" name="jenis_kelamin" required
                                        class="{{ $selectClass }} @error('jenis_kelamin', 'employee') border-red-500 @enderror">
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="Laki-laki" @selected(old('jenis_kelamin', $employee->jenis_kelamin) === 'Laki-laki')>
                                            Laki-laki
                                        </option>
                                        <option value="Perempuan" @selected(old('jenis_kelamin', $employee->jenis_kelamin) === 'Perempuan')>
                                            Perempuan
                                        </option>
                                    </select>
                                </div>
                                @error('jenis_kelamin', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- AGAMA --}}
                            <div>
                                <label for="agama" class="{{ $labelClass }}">
                                    Agama
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-hands-praying {{ $iconClass }}"></i>
                                    <select id="agama" name="agama" required
                                        class="{{ $selectClass }} @error('agama', 'employee') border-red-500 @enderror">
                                        <option value="">Pilih Agama</option>
                                        @foreach (['Islam', 'Kristen Protestan', 'Kristen Katolik', 'Budha', 'Hindu', 'Konghucu'] as $agama)
                                            <option value="{{ $agama }}" @selected(old('agama', $employee->agama) === $agama)>
                                                {{ $agama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('agama', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- STATUS PERNIKAHAN --}}
                            <div>
                                <label for="status_nikah" class="{{ $labelClass }}">
                                    Status Pernikahan
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-heart {{ $iconClass }}"></i>
                                    <select id="status_nikah" name="status_nikah" required
                                        class="{{ $selectClass }} @error('status_nikah', 'employee') border-red-500 @enderror">
                                        <option value="">Pilih Status Pernikahan</option>
                                        @foreach (['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'] as $statusNikah)
                                            <option value="{{ $statusNikah }}" @selected(old('status_nikah', $employee->status_nikah) === $statusNikah)>
                                                {{ $statusNikah }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('status_nikah', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- TINGKAT PENDIDIKAN --}}
                            <div>
                                <label for="tingkat_pendidikan" class="{{ $labelClass }}">
                                    Tingkat Pendidikan
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-graduation-cap {{ $iconClass }} z-10"></i>
                                    <select name="tingkat_pendidikan" id="tingkat_pendidikan" required
                                        data-ranks-url="{{ route('employee.ranks-by-education', ':kode') }}"
                                        data-selected="{{ old('tingkat_pendidikan', $employee->tingkat_pendidikan) }}"
                                        class="{{ $selectClass }} appearance-none @error('tingkat_pendidikan', 'employee') border-red-500 @enderror">
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
                        </div>
                    </div>
                    {{-- WILAYAH ADMINISTRATIF --}}
                    <div>
                        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-100">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-map-location-dot text-blue-600"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-semibold text-slate-800">
                                    Wilayah Administratif
                                </h4>
                                <p class="text-sm text-slate-500">
                                    Lengkapi informasi wilayah tempat tinggal Anda.
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- PROVINSI --}}
                            <div class="relative">
                                <label for="id_provinsi" class="text-sm font-medium text-slate-700 mb-2 block">
                                    Provinsi
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative group">
                                    <i
                                        class="fa-solid fa-earth-asia absolute left-4 top-1/2 -translate-y-1/2 text-blue-500 z-20 pointer-events-none transition-colors group-focus-within:text-blue-600"></i>
                                    <select name="id_provinsi" id="id_provinsi" class="province-select" required
                                        data-selected="{{ old('id_provinsi', $employee->id_provinsi) }}">
                                        <option value="">Pilih Provinsi</option>
                                        @foreach ($master['provinces'] as $province)
                                            <option value="{{ $province->id }}" @selected(old('id_provinsi', $employee->id_provinsi) == $province->id)>
                                                {{ $province->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('id_provinsi', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- KOTA/KABUPATEN --}}
                            <div class="relative">
                                <label for="id_kota" class="text-sm font-medium text-slate-700 mb-2 block">
                                    Kota/Kabupaten
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative group">
                                    <i
                                        class="fa-solid fa-city absolute left-4 top-1/2 -translate-y-1/2 text-indigo-500 z-20 pointer-events-none transition-colors group-focus-within:text-indigo-600"></i>
                                    <select name="id_kota" id="id_kota" class="city-select" required
                                        data-selected="{{ old('id_kota', $employee->id_kota) }}">
                                        <option value="">Pilih Kota/Kabupaten</option>
                                    </select>
                                </div>
                                @error('id_kota', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- KECAMATAN --}}
                            <div class="relative">
                                <label for="id_kecamatan" class="text-sm font-medium text-slate-700 mb-2 block">
                                    Kecamatan
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative group">
                                    <i
                                        class="fa-solid fa-map-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-blue-500 z-20 pointer-events-none transition-colors group-focus-within:text-blue-600"></i>
                                    <select name="id_kecamatan" id="id_kecamatan" class="district-select" required
                                        data-selected="{{ old('id_kecamatan', $employee->id_kecamatan) }}">
                                        <option value="">Pilih Kecamatan</option>
                                    </select>
                                </div>
                                @error('id_kecamatan', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- KELURAHAN/DESA --}}
                            <div class="relative">
                                <label for="id_kelurahan" class="text-sm font-medium text-slate-700 mb-2 block">
                                    Kelurahan/Desa
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative group">
                                    <i
                                        class="fa-solid fa-house absolute left-4 top-1/2 -translate-y-1/2 text-emerald-500 z-20 pointer-events-none transition-colors group-focus-within:text-emerald-600"></i>
                                    <select name="id_kelurahan" id="id_kelurahan" class="village-select" required
                                        data-selected="{{ old('id_kelurahan', $employee->id_kelurahan) }}">
                                        <option value="">Pilih Kelurahan/Desa</option>
                                    </select>
                                </div>
                                @error('id_kelurahan', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- ALAMAT --}}
                            <div class="md:col-span-2">
                                <label for="alamat" class="text-sm font-medium text-slate-700 mb-2 block">
                                    Alamat Lengkap
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-map absolute left-4 top-4 text-slate-400"></i>
                                    <textarea name="alamat" id="alamat" maxlength="255" autocomplete="off" rows="3" required
                                        class="w-full min-h-12 pl-11 pr-4 pt-3 pb-3 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition resize-none">{{ trim(old('alamat', $employee->alamat)) }}</textarea>
                                </div>
                                @error('alamat', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    {{-- INFORMASI KEPEGAWAIAN --}}
                    <div>
                        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-100">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-briefcase text-blue-600"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-semibold text-slate-800">
                                    Informasi Kepegawaian
                                </h4>
                                <p class="text-sm text-slate-500">
                                    Informasi status dan riwayat kepegawaian Anda.
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- JENIS PEGAWAI --}}
                            <div>
                                <label for="status_pegawai" class="{{ $labelClass }}">
                                    Jenis Pegawai <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-users {{ $iconClass }}"></i>
                                    <select id="status_pegawai" name="status_pegawai" required
                                        class="{{ $selectClass }} @error('status_pegawai', 'employee') border-red-500 @enderror">
                                        <option value="">Pilih Jenis Pegawai</option>
                                        <option value="PNS" @selected(old('status_pegawai', $employee->status_pegawai) === 'PNS')>PNS</option>
                                        <option value="PPPK" @selected(old('status_pegawai', $employee->status_pegawai) === 'PPPK')>PPPK</option>
                                    </select>
                                </div>
                                @error('status_pegawai', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- TANGGAL MASUK --}}
                            <div>
                                <label for="tanggal_masuk" class="{{ $labelClass }}">
                                    Tanggal Masuk <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-calendar-check {{ $iconClass }}"></i>
                                    <input type="date" id="tanggal_masuk" name="tanggal_masuk" required
                                        value="{{ old('tanggal_masuk', $employee->tanggal_masuk ? \Illuminate\Support\Carbon::parse($employee->tanggal_masuk)->format('Y-m-d') : '') }}"
                                        class="{{ $inputClass }} @error('tanggal_masuk', 'employee') border-red-500 @enderror">
                                </div>
                                @error('tanggal_masuk', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- UNIT KERJA --}}
                            <div>
                                <label for="kode_unit_kerja" class="{{ $labelClass }}">
                                    Unit Kerja <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-building {{ $iconClass }}"></i>
                                    <select id="kode_unit_kerja" name="kode_unit_kerja" required
                                        class="{{ $selectClass }} @error('kode_unit_kerja', 'employee') border-red-500 @enderror">
                                        <option value="">Pilih Unit Kerja</option>
                                        @foreach ($master['departments'] as $department)
                                            <option value="{{ $department->kode }}" @selected(old('kode_unit_kerja', $employee->kode_unit_kerja) == $department->kode)>
                                                {{ $department->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('kode_unit_kerja', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- JABATAN --}}
                            <div>
                                <label for="kode_jabatan" class="{{ $labelClass }}">
                                    Jabatan <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-user-tie {{ $iconClass }}"></i>
                                    <select id="kode_jabatan" name="kode_jabatan" required
                                        class="{{ $selectClass }} @error('kode_jabatan', 'employee') border-red-500 @enderror">
                                        <option value="">Pilih Jabatan</option>
                                        @foreach ($master['positions'] as $position)
                                            <option value="{{ $position->kode }}" @selected(old('kode_jabatan', $employee->kode_jabatan) == $position->kode)>
                                                {{ $position->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('kode_jabatan', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
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
                            {{-- PANGKAT --}}
                            {{-- <div class="relative">
                                <label for="id_pangkat" class="text-sm font-medium text-slate-700 mb-2 block">
                                    Pangkat/Golongan
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <i
                                        class="fa-solid fa-medal absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>

                                    <select name="id_pangkat" id="id_pangkat"
                                        class="w-full h-12 pl-11 pr-10 bg-white border border-slate-200 rounded-2xl
                   focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100
                   outline-none appearance-none">
                                        <option value="">Pilih Pangkat/Golongan</option>

                                        @foreach ($master['ranks'] as $rank)
                                            <option value="{{ $rank->id }}" @selected(old('id_pangkat', $employee->id_pangkat) == $rank->id)>
                                                {{ $rank->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @error('id_pangkat')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="relative">
                                <label for="kode_pppk" class="text-sm font-medium text-slate-700 mb-2 block">
                                    Golongan PPPK
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <i
                                        class="fa-solid fa-layer-group absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>

                                    <select name="kode_pppk" id="kode_pppk"
                                        class="w-full h-12 pl-11 pr-10 bg-white border border-slate-200 rounded-2xl
                   focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100
                   outline-none appearance-none">
                                        <option value="">Pilih Golongan PPPK</option>

                                        @foreach ($master['pppk'] as $pppk)
                                            <option value="{{ $pppk->kode }}" @selected(old('kode_pppk', $employee->kode_pppk) == $pppk->kode)>
                                                {{ $pppk->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @error('kode_pppk')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div> --}}
                            {{-- TMT PANGKAT LAMA --}}
                            <div>
                                <label for="tmt_pangkat_lama" class="{{ $labelClass }}">
                                    TMT Pangkat Lama <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-calendar-days {{ $iconClass }}"></i>
                                    <input type="date" id="tmt_pangkat_lama" name="tmt_pangkat_lama" required
                                        value="{{ old('tmt_pangkat_lama', $employee->tmt_pangkat_lama ? \Illuminate\Support\Carbon::parse($employee->tmt_pangkat_lama)->format('Y-m-d') : '') }}"
                                        class="{{ $inputClass }} @error('tmt_pangkat_lama', 'employee') border-red-500 @enderror">
                                </div>
                                @error('tmt_pangkat_lama', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- TMT PANGKAT BARU --}}
                            <div>
                                <label for="tmt_pangkat_baru" class="{{ $labelClass }}">
                                    TMT Pangkat Baru
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-calendar-check {{ $iconClass }}"></i>
                                    <input type="date" id="tmt_pangkat_baru" name="tmt_pangkat_baru" readonly
                                        value="{{ old('tmt_pangkat_baru', $employee->tmt_pangkat_baru ? \Illuminate\Support\Carbon::parse($employee->tmt_pangkat_baru)->format('Y-m-d') : '') }}"
                                        class="{{ $inputClass }} @error('tmt_pangkat_baru', 'employee') border-red-500 @enderror">
                                </div>
                                @error('tmt_pangkat_baru', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- TMT KGB LAMA --}}
                            <div>
                                <label for="tmt_kgb_lama" class="{{ $labelClass }}">
                                    TMT KGB Lama <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-calendar-days {{ $iconClass }}"></i>
                                    <input type="date" id="tmt_kgb_lama" name="tmt_kgb_lama" required
                                        value="{{ old('tmt_kgb_lama', $employee->tmt_kgb_lama ? \Illuminate\Support\Carbon::parse($employee->tmt_kgb_lama)->format('Y-m-d') : '') }}"
                                        class="{{ $inputClass }} @error('tmt_kgb_lama', 'employee') border-red-500 @enderror">
                                </div>
                                @error('tmt_kgb_lama', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- TMT KGB BARU --}}
                            <div>
                                <label for="tmt_kgb_baru" class="{{ $labelClass }}">
                                    TMT KGB Baru
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-calendar-check {{ $iconClass }}"></i>
                                    <input type="date" id="tmt_kgb_baru" name="tmt_kgb_baru" readonly
                                        value="{{ old('tmt_kgb_baru', $employee->tmt_kgb_baru ? \Illuminate\Support\Carbon::parse($employee->tmt_kgb_baru)->format('Y-m-d') : '') }}"
                                        class="{{ $inputClass }} @error('tmt_kgb_baru', 'employee') border-red-500 @enderror">
                                </div>
                                @error('tmt_kgb_baru', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- TANGGAL PENSIUN --}}
                            <div>
                                <label for="tanggal_pensiun" class="{{ $labelClass }}">
                                    Tanggal Pensiun
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-calendar-minus {{ $iconClass }}"></i>
                                    <input type="date" id="tanggal_pensiun" name="tanggal_pensiun" readonly
                                        value="{{ old('tanggal_pensiun', $employee->tanggal_pensiun ? \Illuminate\Support\Carbon::parse($employee->tanggal_pensiun)->format('Y-m-d') : '') }}"
                                        class="{{ $inputClass }} @error('tanggal_pensiun', 'employee') border-red-500 @enderror">
                                </div>
                                @error('tanggal_pensiun', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- TMT PENSIUN --}}
                            <div>
                                <label for="tmt_pensiun" class="{{ $labelClass }}">
                                    TMT Pensiun
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-calendar-check {{ $iconClass }}"></i>
                                    <input type="date" id="tmt_pensiun" name="tmt_pensiun" readonly
                                        value="{{ old('tmt_pensiun', $employee->tmt_pensiun ? \Illuminate\Support\Carbon::parse($employee->tmt_pensiun)->format('Y-m-d') : '') }}"
                                        class="{{ $inputClass }} @error('tmt_pensiun', 'employee') border-red-500 @enderror">
                                </div>
                                @error('tmt_pensiun', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- MASA KERJA TAHUN --}}
                            <div>
                                <label for="tahun_masa_kerja" class="{{ $labelClass }}">
                                    Masa Kerja (Tahun) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-calendar {{ $iconClass }}"></i>
                                    <input type="number" id="tahun_masa_kerja" name="tahun_masa_kerja"
                                        min="0" max="255" required
                                        value="{{ old('tahun_masa_kerja', $employee->tahun_masa_kerja) }}"
                                        class="{{ $inputClass }} @error('tahun_masa_kerja', 'employee') border-red-500 @enderror">
                                </div>
                                @error('tahun_masa_kerja', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- MASA KERJA BULAN --}}
                            <div>
                                <label for="bulan_masa_kerja" class="{{ $labelClass }}">
                                    Masa Kerja (Bulan) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fa-solid fa-calendar-days {{ $iconClass }}"></i>
                                    <input type="number" id="bulan_masa_kerja" name="bulan_masa_kerja"
                                        min="0" max="11" required
                                        value="{{ old('bulan_masa_kerja', $employee->bulan_masa_kerja) }}"
                                        class="{{ $inputClass }} @error('bulan_masa_kerja', 'employee') border-red-500 @enderror">
                                </div>
                                @error('bulan_masa_kerja', 'employee')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="mb-4 md:col-span-2">
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
                                    {{-- Informasi dokumen tersimpan --}}
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
                                    {{-- Input upload dokumen --}}
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
                                    {{-- Status dokumen --}}
                                    <p id="statusDokumen_{{ $jenis }}"
                                        class="mt-2 text-xs {{ $fileDokumenTersedia ? 'text-emerald-700' : ($dokumenSaatIni ? 'text-amber-700' : 'text-slate-500') }}"
                                        aria-live="polite">
                                        {{ $fileDokumenTersedia ? 'Dokumen telah diunggah.' : ($dokumenSaatIni ? 'File dokumen tidak ditemukan. Pilih file untuk mengganti.' : 'Dokumen ini belum diunggah.') }}
                                    </p>
                                    {{-- Pesan validasi --}}
                                    @error("dokumen.$jenis")
                                        <p class="mt-2 text-sm text-red-500">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                    {{-- TOMBOL SIMPAN --}}
                    <div class="pt-6 border-t border-slate-200 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white px-6 py-3 rounded-xl font-medium shadow-sm hover:shadow-md transition duration-200">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Simpan Data Pegawai</span>
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="border border-amber-200 bg-amber-50 rounded-2xl p-5 mb-6">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-info text-amber-600 text-xl"></i>
                    <div>
                        <h3 class="font-semibold text-amber-800">Data Pegawai Belum Terhubung</h3>
                        <p class="text-sm text-amber-700">
                            Akun Anda belum terhubung dengan data pegawai. Silakan hubungi Admin.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
