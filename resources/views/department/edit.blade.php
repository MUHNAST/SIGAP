<!-- MODAL Unit Kerja-->
@foreach ($data as $department)
    <div id="modalEditUnitKerja{{ $department->kode }}" role="dialog" aria-modal="true"
        class="modal fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <!-- CARD -->
        <div
            class="bg-white w-full max-w-4xl h-[95vh] rounded-[32px] shadow-[0_20px_60px_rgba(0,0,0,0.15)] overflow-hidden flex flex-col">
            <!-- HEADER -->
            <div class="relative bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 px-8 py-7">
                <div class="absolute right-0 top-0 opacity-10">
                    <i class="fa-solid fa-medal text-[180px]"></i>
                </div>
                <div class="relative flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center text-white text-2xl">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-white">
                                Edit Unit Kerja
                            </h2>
                            <p class="text-emerald-100">
                                Kelola data Unit Kerja pegawai
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
            <form id="UnitKerjaForm{{ $department->kode }}" method="POST"
                action="{{ route('department.update', $department->kode) }}" class="flex-1 flex flex-col min-h-0">
                @csrf
                @method('PUT')
                <!-- SCROLL AREA -->
                <div class="flex-1 min-h-0 overflow-y-auto p-6 md:p-8 space-y-8">
                    <!-- SECTION TITLE -->
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <i class="fa-solid fa-id-badge"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">
                            Informasi Unit Kerja
                        </h3>
                    </div>
                    <!-- GRID -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        {{-- Kode --}}
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                Kode Unit Kerja <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-hashtag absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" name="kode" value="{{ old('kode', $department->kode) }}"
                                    placeholder="Masukkan kode Unit Kerja" maxlength="8" required autocomplete="off"
                                    class="w-full h-11 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                    focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none">
                            </div>
                            @error('kode')
                                <p class="mt-1.5 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- nama -->
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                Nama Unit Kerja
                            </label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-building absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" name="nama" placeholder="Masukkan Nama Unit Kerja"
                                    value="{{ old('nama', $department->nama ?? '') }}" autofocus autocomplete="off"
                                    class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none">
                            </div>
                            @error('nama')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <div class="relative">
                            <label for="status{{ $department->kode }}"
                                class="block mb-2 text-sm font-medium text-slate-700">
                                Status Unit Kerja <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-user-check absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <select id="status{{ $department->kode }}" name="status"
                                    class="w-full h-12 pl-11 pr-10 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none appearance-none">
                                    <option value="1"
                                        {{ old('status', $department->status) == 1 ? 'selected' : '' }}>
                                        Aktif
                                    </option>
                                    <option value="0"
                                        {{ old('status', $department->status) == 0 ? 'selected' : '' }}>
                                        Nonaktif
                                    </option>
                                </select>
                            </div>
                            @error('status')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                Deskripsi
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-file-lines absolute left-4 top-4 text-slate-400"></i>
                                <textarea rows="3" name="deskripsi" maxlength="500" placeholder="Masukkan deskripsi unit kerja" autocomplete="off"
                                    class="w-full min-h-12 pl-11 pr-4 pt-3 pb-3 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 outline-none transition resize-none">{{ old('deskripsi', $department->deskripsi ?? '') }}</textarea>
                            </div>
                            @error('deskripsi')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                    </div>
                    <!-- INFO BOX -->
                    <div class="flex items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <i class="fa-solid fa-circle-info"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-800">Informasi</h4>
                            <p class="text-sm text-slate-500">
                                Pastikan data diisi dengan benar sebelum disimpan.
                            </p>
                        </div>
                    </div>
                </div>
            </form>
            <!-- FOOTER -->
            <div class="px-8 py-5 border-t border-slate-100 bg-slate-50 flex justify-between items-center">
                <p class="text-sm text-slate-500">
                    Data akan digunakan untuk sistem kepegawaian
                </p>
                <div class="flex gap-3">
                    <button onclick="closeAllModal()" type="button"
                        class="px-6 py-3 rounded-2xl border border-slate-200 hover:bg-white transition">
                        Batal
                    </button>
                    <button type="submit" form="UnitKerjaForm{{ $department->kode }}"
                        class="px-6 py-3 rounded-2xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-lg shadow-emerald-200 transition">
                        <i class="fa-solid fa-floppy-disk mr-2"></i>
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endforeach
