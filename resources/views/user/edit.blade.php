@foreach ($data as $user)
    <div id="modalEditUser{{ $user->id }}" role="dialog" aria-modal="true"
        class="modal fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div
            class="bg-white w-full max-w-6xl h-[95vh] rounded-[32px] shadow-[0_20px_60px_rgba(0,0,0,.15)] overflow-hidden flex flex-col">
            {{-- HEADER --}}
            <div class="relative bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 px-8 py-7">
                <div class="absolute right-0 top-0 opacity-10">
                    <i class="fa-solid fa-user-pen text-[180px]"></i>
                </div>
                <div class="relative flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center text-white text-2xl">
                            <i class="fa-solid fa-user-pen"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-white">
                                Edit Admin Unit Kerja
                            </h2>
                            <p class="text-blue-100">
                                Mengubah data akun administrator
                            </p>
                        </div>
                    </div>
                    <button type="button" onclick="closeAllModal()"
                        class="w-11 h-11 rounded-xl bg-white/20 hover:bg-white/30 text-white transition">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
            <form id="editUserForm{{ $user->id }}" action="{{ route('user.update', $user->id) }}" method="POST"
                enctype="multipart/form-data" class="flex-1 flex flex-col min-h-0">
                @csrf
                @method('PUT')
                <div class="flex-1 overflow-y-auto p-6 md:p-8">
                    {{-- GLOBAL ERROR --}}
                    @if ($errors->edit->any())
                        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4">
                            <div class="flex gap-3">
                                <i class="fa-solid fa-circle-exclamation text-red-500 mt-1"></i>
                                <div>
                                    <h4 class="font-semibold text-red-700">
                                        Terjadi kesalahan
                                    </h4>
                                    <ul class="mt-2 text-sm text-red-600 space-y-1">
                                        @foreach ($errors->edit->all() as $error)
                                            <li>• {{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                    {{-- TITLE --}}
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                            <i class="fa-solid fa-user"></i>
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
                                        <img id="previewEdit{{ $user->id }}"
                                            src="{{ $user->foto ? asset('storage/' . $user->foto) : asset('images/default-user.png') }}"
                                            class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg">
                                        <label for="fotoEdit{{ $user->id }}"
                                            class="absolute bottom-0 right-0 w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center cursor-pointer hover:bg-blue-700 transition">
                                            <i class="fa-solid fa-camera"></i>
                                        </label>
                                    </div>
                                    <h4 class="mt-5 font-semibold text-slate-700">
                                        Upload Foto
                                    </h4>
                                    <p id="namaFileEdit{{ $user->id }}"
                                        class="text-sm text-center text-slate-500 mt-1">
                                        Klik ikon kamera untuk memilih foto
                                        <br>
                                        Format JPG, JPEG, PNG • Maksimal 2 MB
                                    </p>
                                    <input type="file" id="fotoEdit{{ $user->id }}" name="foto"
                                        accept="image/jpeg,image/png" class="hidden image-preview-input"
                                        data-preview="#previewEdit{{ $user->id }}"
                                        data-filename="#namaFileEdit{{ $user->id }}">
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
                                        Nama <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <i
                                            class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input type="text" name="nama" value="{{ old('nama', $user->nama) }}"
                                            placeholder="Masukkan nama lengkap" autofocus autocomplete="off"
                                            class="w-full h-12 pl-11 pr-4 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none">
                                    </div>
                                    @error('nama')
                                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                                {{-- USERNAME --}}
                                <div>
                                    <label class="text-sm font-medium text-slate-700 mb-2 block">
                                        Username <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <i
                                            class="fa-solid fa-at absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input type="text" name="username" value="{{ old('username', $user->username) }}" placeholder="Masukkan username" autocomplete="off"
                                            class="w-full h-12 pl-11 pr-4 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none">
                                    </div>
                                    @error('username')
                                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                                {{-- EMAIL --}}
                                <div>
                                    <label class="text-sm font-medium text-slate-700 mb-2 block">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="contoh@email.com" autocomplete="off"
                                            class="w-full h-12 pl-11 pr-4 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none">
                                    </div>
                                    @error('email')
                                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                                {{-- NOMOR HP --}}
                                <div>
                                    <label class="text-sm font-medium text-slate-700 mb-2 block">
                                        Nomor HP <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <i
                                            class="fa-solid fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input type="text" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}"
                                            placeholder="08xxxxxxxxxx" maxlength="14" minlength="10" pattern="08[0-9]{8,12}" oninput="this.value=this.value.replace(/[^0-9]/g,'')" autocomplete="off"
                                            class="w-full h-12 pl-11 pr-4 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none">
                                    </div>
                                    @error('no_hp')
                                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                                {{-- UNIT KERJA --}}
                                <div class="md:col-span-2">
                                    <label class="text-sm font-medium text-slate-700 mb-2 block">
                                        Unit Kerja <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <i
                                            class="fa-solid fa-building absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 z-10"></i>
                                        <select name="kode_unit_kerja"
                                            class="w-full h-12 pl-11 pr-4 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none">
                                            @foreach ($user->available_departments as $department)
                                                <option value="{{ $department->kode }}"
                                                    {{ old('kode_unit_kerja', $user->kode_unit_kerja) == $department->kode ? 'selected' : '' }}>
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
                    {{-- INFO PASSWORD --}}
                    <div class=" mt-6 rounded-2xl border border-blue-100 bg-blue-50 p-4 flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-800">
                                Informasi Password
                            </h4>
                            <p class="text-sm text-slate-500 mt-1">
                                Password default adalah <b>123456789</b>.
                                Gunakan tombol <b>Reset Password</b> apabila ingin
                                mengembalikan password ke nilai default.
                            </p>
                        </div>
                    </div>
                </div>
            </form>
            {{-- FOOTER --}}
            <div class="px-8 py-5 border-t border-slate-100 bg-slate-50 flex justify-between items-center">
                <p class="text-sm text-slate-500">
                    Perubahan akan langsung diterapkan pada akun administrator.
                </p>
                <div class="flex gap-3">
                    <button type="button" onclick="closeAllModal()"
                        class="px-6 py-3 rounded-2xl border border-slate-200 hover:bg-white transition">
                        Batal
                    </button>
                    <button type="submit" form="editUserForm{{ $user->id }}"
                        class="px-6 py-3 rounded-2xl bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-200 transition">
                        <i class="fa-solid fa-floppy-disk mr-2"></i>
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endforeach
