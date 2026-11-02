<!-- MODAL EDIT Golongan -->
@foreach ($data as $pppk)
    <div id="modalEditPangkat{{ $pppk->kode }}" role="dialog" aria-modal="true"
        class="modal fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <!-- CARD -->
        <div
            class="bg-white w-full max-w-4xl max-h-[90vh] rounded-3xl shadow-[0_20px_60px_rgba(0,0,0,0.15)] overflow-hidden flex flex-col">
            <!-- HEADER -->
            <div class="relative bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 px-6 py-5">
                <div class="absolute right-0 top-0 opacity-10">
                    <i class="fa-solid fa-medal text-[120px]"></i>
                </div>
                <div class="relative flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center text-white text-xl">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">
                                Edit Golongan
                            </h2>
                            <p class="text-sm text-emerald-100">
                                Perbarui data golongan pegawai PPPK
                            </p>
                        </div>
                    </div>
                    <button onclick="closeAllModal()" type="button"
                        class="w-10 h-10 rounded-xl bg-white/20 hover:bg-white/30 text-white transition">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
            <!-- FORM -->
            <form id="pangkatForm{{ $pppk->kode }}" method="POST" action="{{ route('pppk.update', $pppk->kode) }}"
                class="flex flex-col">
                @csrf
                @method('PUT')
                <!-- CONTENT -->
                <div class="p-6 space-y-6">
                    <!-- SECTION TITLE -->
                    <div class="flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <i class="fa-solid fa-id-badge"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800">
                            Informasi Golongan PPPK
                        </h3>
                    </div>
                    <!-- GRID -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Kode -->
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                Kode Golongan PPPK <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-hashtag absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                </i>
                                <input type="text" name="kode" placeholder="Masukkan kode golongan PPPK"
                                    value="{{ old('kode', $pppk->kode ?? '') }}" required autocomplete="off" maxlength="5"
                                    class="w-full h-11 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-xl
                                    focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none">
                            </div>
                            @error('kode')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- Nama -->
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                Nama Golongan PPPK <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-medal absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                </i>
                                <input type="text" name="nama" placeholder="Masukkan nama Golongan PPPK"
                                    value="{{ old('nama', $pppk->nama ?? '') }}" required autocomplete="off"
                                    class="w-full h-11 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-xl
                                    focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none">
                            </div>
                            @error('nama')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                Urutan Golongan PPPK <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-list-ol absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="number" name="urutan_golongan" placeholder="Masukkan urutan Golongan PPPK"
                                    value="{{ old('urutan_golongan', $pppk->urutan_golongan ?? '') }}" min="1"
                                    max="255" required autocomplete="off"
                                    class="w-full h-11 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-xl
                                    focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none">
                            </div>
                            @error('urutan_golongan')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                    <!-- INFO BOX -->
                    <div class="flex items-start gap-3 rounded-xl border border-emerald-100 bg-emerald-50 p-3">
                        <div
                            class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-circle-info"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-800">
                                Informasi
                            </h4>
                            <p class="text-xs text-slate-500 mt-1">
                                Pastikan data diisi dengan benar sebelum disimpan.
                            </p>
                        </div>
                    </div>
                </div>
                <!-- FOOTER -->
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end items-center">
                    <div class="flex gap-3">
                        <button onclick="closeAllModal()" type="button"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 hover:bg-white transition text-sm">
                            Batal
                        </button>
                        <button type="submit" form="pangkatForm{{ $pppk->kode }}"
                            class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white
                            hover:bg-emerald-700 shadow-lg shadow-emerald-200 transition text-sm">
                            <i class="fa-solid fa-floppy-disk mr-2"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endforeach
