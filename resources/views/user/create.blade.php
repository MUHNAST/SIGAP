<!-- MODAL TAMBAH USER -->
<div id="modalTambahUser" role="dialog" aria-modal="true"
    class="modal fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div
        class="bg-white w-full max-w-6xl h-[95vh] rounded-[32px] shadow-[0_20px_60px_rgba(0,0,0,0.15)] overflow-hidden flex flex-col">
        <!-- HEADER -->
        <div class="relative bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 px-8 py-7">
            <div class="absolute right-0 top-0 opacity-10">
                <i class="fa-solid fa-user-shield text-[180px]"></i>
            </div>
            <div class="relative flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center text-white text-2xl">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">
                            Tambah Admin Unit Kerja
                        </h2>
                        <p class="text-blue-100">
                            Membuat akun admin untuk setiap unit kerja
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeAllModal()"
                    class="w-11 h-11 rounded-xl bg-white/20 hover:bg-white/30 text-white transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <!-- FORM -->
        <form id="UserForm" action="{{ route('user.store') }}" method="POST" enctype="multipart/form-data"
            class="flex-1 flex flex-col min-h-0">
            @csrf
            <div class="flex-1 overflow-y-auto p-6 md:p-8 space-y-8">
                {{-- TITLE --}}
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">
                        Informasi Administrator
                    </h3>
                </div>
                {{-- <div class="grid lg:grid-cols-3 gap-8"> --}}
                <div class="grid lg:grid-cols-[380px_minmax(0,1fr)] gap-8">
                    {{-- FOTO --}}
                    <div>
                        <label class="text-sm font-medium text-slate-700 mb-3 block">
                            Foto Profil
                        </label>
                        <div
                            class="border-2 border-dashed border-slate-300 rounded-3xl p-6 bg-slate-50 hover:border-blue-400 hover:bg-blue-50 transition">
                            <div class="flex flex-col items-center">
                                <div class="relative">
                                    <img id="previewTambah" src="{{ asset('images/default-user.png') }}"
                                        class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg">
                                    <label for="fotoTambah"
                                        class="absolute bottom-0 right-0 w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center cursor-pointer hover:bg-blue-700 transition">
                                        <i class="fa-solid fa-camera"></i>
                                    </label>
                                </div>
                                <h4 class="mt-5 font-semibold text-slate-700">
                                    Upload Foto
                                </h4>
                                <p id="namaFileTambah" class="text-sm text-center text-slate-500 mt-1">
                                    Klik ikon kamera untuk memilih foto
                                    <br>
                                    Format JPG, JPEG, PNG • Maksimal 2 MB
                                </p>
                                <input id="fotoTambah" type="file" name="foto" accept="image/jpeg,image/png"
                                    class="hidden image-preview-input" data-preview="#previewTambah"
                                    data-filename="#namaFileTambah">
                            </div>
                        </div>
                        @error('foto')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    {{-- FORM --}}
                    {{-- <div class="lg:col-span-2"> --}}
                    <div class="min-w-0">
                        {{-- <div class="grid md:grid-cols-2 gap-5"> --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            {{-- NAMA --}}
                            <div>
                                <label class="text-sm font-medium text-slate-700 mb-2 block">
                                    Nama Lengkap
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-id-card absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="text" name="nama" value="{{ old('nama') }}"
                                        placeholder="Masukkan nama lengkap" autofocus autocomplete="off"
                                        class="w-full h-12 pl-11 pr-4 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none">
                                </div>
                                @error('nama')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- Username --}}
                            <div>
                                <label class="text-sm font-medium text-slate-700 mb-2 block">
                                    Username
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="text" name="username" value="{{ old('username') }}" autocomplete="off"
                                        placeholder="Masukkan username"
                                        class="w-full h-12 pl-11 pr-4 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none">
                                </div>
                                @error('username')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- Email --}}
                            <div>
                                <label class="text-sm font-medium text-slate-700 mb-2 block">
                                    Email
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="email" name="email" value="{{ old('email') }}" autocomplete="off"
                                        placeholder="admin@email.com"
                                        class="w-full h-12 pl-11 pr-4 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none">
                                </div>
                                @error('email')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- No HP --}}
                            <div>
                                <label class="text-sm font-medium text-slate-700 mb-2 block">
                                    Nomor HP
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="text" name="no_hp" value="{{ old('no_hp') }}" autocomplete="off"
                                        placeholder="08xxxxxxxxxx" maxlength="14" minlength="10" pattern="08[0-9]{8,12}"
                                        oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                        class="w-full h-12 pl-11 pr-4 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none">
                                </div>
                                @error('no_hp')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- Unit Kerja --}}
                            <div class="md:col-span-2">
                                <div>
                                    <label class="text-sm font-medium text-slate-700 mb-2 block">
                                        Unit Kerja
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <i
                                            class="fa-solid fa-building absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                                        <select name="kode_unit_kerja"
                                            class="w-full h-12 pl-11 pr-4 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none">
                                            <option value="">Pilih Unit Kerja</option>
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->kode }}"
                                                    {{ old('kode_unit_kerja') == $department->kode ? 'selected' : '' }}>
                                                    {{ $department->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('kode_unit_kerja')
                                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50 p-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                        <i class="fa-solid fa-key"></i>
                    </div>

                    <div>
                        <h4 class="font-semibold text-slate-800">
                            Informasi Password
                        </h4>
                        <p class="text-sm text-slate-500">
                            Password default adalah <strong>123456789</strong>.
                            Admin dapat mengganti password setelah berhasil login.
                        </p>
                    </div>
                </div>
            </div>

        </form>
        <!-- FOOTER -->
        <div class="px-8 py-5 border-t border-slate-100 bg-slate-50 flex justify-between items-center">
            <p class="text-sm text-slate-500">
                Data akan digunakan untuk proses autentikasi admin sistem.
            </p>
            <div class="flex gap-3">
                <button type="button" onclick="closeAllModal()"
                    class="px-6 py-3 rounded-2xl border border-slate-200 hover:bg-white transition">
                    Batal
                </button>
                <button type="submit" form="UserForm"
                    class="px-6 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-200 transition">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>
                    Simpan
                </button>
            </div>
        </div>
    </div>
</div>
