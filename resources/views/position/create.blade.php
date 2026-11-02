<!-- MODAL TAMBAH JABATAN -->
<div id="modalTambahJabatan" role="dialog" aria-modal="true"
    class="modal fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div
        class="bg-white w-full max-w-4xl h-[85vh] rounded-[32px] shadow-[0_20px_60px_rgba(0,0,0,0.15)] overflow-hidden flex flex-col">
        <!-- HEADER -->
        <div class="relative bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 px-8 py-6 shrink-0">
            <div class="absolute right-0 top-0 opacity-10">
                <i class="fa-solid fa-medal text-[150px]"></i>
            </div>
            <div class="relative flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-white text-2xl">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">
                            Tambah Jabatan
                        </h2>
                        <p class="text-emerald-100 text-sm">
                            Kelola data jabatan pegawai
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeAllModal()"
                    class="w-10 h-10 rounded-xl bg-white/20 hover:bg-white/30 text-white transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <!-- FORM -->
        <form id="JabatanForm" method="POST" action="{{ route('position.store') }}"
            class="flex-1 flex flex-col min-h-0">
            @csrf
            <!-- BODY -->
            <div class="flex-1 min-h-0 overflow-y-auto px-6 py-5 md:px-8 md:py-6">
                <!-- SECTION TITLE -->
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                        <i class="fa-solid fa-id-badge"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">
                        Informasi Jabatan
                    </h3>
                </div>
                <!-- GRID 2 KOLOM -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- KODE JABATAN -->
                    <div>
                        <label class="text-sm font-medium text-slate-700 mb-2 block">
                            Kode Jabatan <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-hashtag absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" name="kode" value="{{ old('kode') }}"
                                placeholder="Masukkan kode jabatan" maxlength="20" required autocomplete="off"
                                class="w-full h-11 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none">
                        </div>
                        @error('kode')
                            <p class="mt-1.5 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <!-- NAMA JABATAN -->
                    <div>
                        <label class="text-sm font-medium text-slate-700 mb-2 block">
                            Nama Jabatan <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i
                                class="fa-solid fa-briefcase absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" name="nama" value="{{ old('nama') }}"
                                placeholder="Masukkan nama jabatan" maxlength="255" required autocomplete="off"
                                class="w-full h-11 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none">
                        </div>
                        @error('nama')
                            <p class="mt-1.5 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <!-- MASA PENSIUN -->
                    <div>
                        <label class="text-sm font-medium text-slate-700 mb-2 block">
                            Masa Pensiun (Tahun) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i
                                class="fa-solid fa-calendar-days absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="number" name="usia_pensiun" value="{{ old('usia_pensiun') }}"
                                placeholder="Masukkan masa pensiun" min="58" max="70" required autocomplete="off"
                                class="w-full h-11 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none">
                        </div>
                        @error('usia_pensiun')
                            <p class="mt-1.5 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <!-- DESKRIPSI FULL WIDTH -->
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-slate-700 mb-2 block">
                            Deskripsi
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-file-lines absolute left-4 top-4 text-slate-400"></i>
                            <textarea name="deskripsi" rows="2" maxlength="255" autocomplete="off"
                                placeholder="Masukkan deskripsi tentang jabatan"
                                class="w-full pl-11 pr-4 pt-3 pb-3 bg-slate-50 border border-slate-200 rounded-2xl
                                focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100
                                outline-none transition resize-none">{{ old('deskripsi') }}</textarea>
                        </div>
                        @error('deskripsi')
                            <p class="mt-1.5 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
                <!-- INFO BOX -->
                <div class="flex items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 mt-5">
                    <div
                        class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-800">
                            Informasi
                        </h4>
                        <p class="text-sm text-slate-500">
                            Pastikan data diisi dengan benar sebelum disimpan.
                        </p>
                    </div>
                </div>
            </div>
        </form>
        <!-- FOOTER -->
        <div class="px-8 py-4 border-t border-slate-100 bg-slate-50 flex justify-between items-center shrink-0">
            <p class="text-sm text-slate-500">
                Data akan digunakan untuk sistem kepegawaian
            </p>
            <div class="flex gap-3">
                <button type="button" onclick="closeAllModal()"
                    class="px-6 py-2.5 rounded-2xl border border-slate-200 hover:bg-white transition">
                    Batal
                </button>
                <button type="submit" form="JabatanForm"
                    class="px-6 py-2.5 rounded-2xl bg-emerald-600 text-white hover:bg-emerald-700
                    shadow-lg shadow-emerald-200 transition">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>
                    Simpan
                </button>
            </div>
        </div>
    </div>
</div>
