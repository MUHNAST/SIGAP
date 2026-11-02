<!-- MODAL Edit GAJI -->
@foreach ($data as $salary)
    <div id="modalEditGaji{{ $salary->id }}" role="dialog" aria-modal="true"
        class="modal fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div
            class="bg-white w-full max-w-4xl max-h-[90vh] rounded-[32px] shadow-[0_20px_60px_rgba(0,0,0,0.15)] overflow-hidden flex flex-col">
            <!-- HEADER -->
            <div class="relative bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 px-8 py-7">
                <div class="absolute right-0 top-0 opacity-10">
                    <i class="fa-solid fa-money-bill-wave text-[180px]"></i>
                </div>
                <div class="relative flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center text-white text-2xl">
                            <i class="fa-solid fa-money-check-dollar"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-white">
                                Edit Gaji
                            </h2>
                            <p class="text-emerald-100">
                                Kelola data gaji pegawai
                            </p>
                        </div>
                    </div>
                    <button type="button" onclick="closeAllModal()"
                        class="w-11 h-11 rounded-xl bg-white/20 hover:bg-white/30 text-white transition">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
            <!-- BODY -->
            <form id="salaryForm{{ $salary->id }}" method="POST" action="{{ route('salary.update', $salary->id) }}"
                class="flex-1 flex flex-col min-h-0">
                @csrf
                @method('PUT')
                <div class="flex-1 overflow-y-auto p-6 md:p-8 space-y-8">
                    <!-- TITLE -->
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <i class="fa-solid fa-sack-dollar"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">
                            Informasi Gaji
                        </h3>
                    </div>
                    <!-- FORM -->
                    <div class="grid md:grid-cols-2 gap-5">
                        <!-- Pangkat dan Golongan -->
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                Pangkat dan Golongan <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">
                                <i
                                    class="fa-solid fa-layer-group absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>

                                <select id="id_pangkat" name="id_pangkat" required autofocus
                                    class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none">
                                    <option value="">-- Pilih Pangkat dan Golongan --</option>

                                    @foreach ($ranks as $rank)
                                        <option value="{{ $rank->id }}"
                                            {{ old('id_pangkat', $salary->id_pangkat) == $rank->id ? 'selected' : '' }}>
                                            {{ $rank->nama }} ({{ $rank->kode }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @error('id_pangkat')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <!-- MASA KERJA -->
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                Masa Kerja (Tahun) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-calendar-days absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="number" name="tahun_masa_kerja" autocomplete="off" min="0" max="33"
                                    oninput="this.value = this.value.replace(/\D/g, '').slice(0, 2);
                                        if (this.value > 80) {
                                            this.value = 80;"
                                    value="{{ old('tahun_masa_kerja', $salary->tahun_masa_kerja ?? '') }}" required
                                    placeholder="Masukkan masa kerja" autofocus
                                    class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                       focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 outline-none">
                            </div>
                            @error('tahun_masa_kerja')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        <!-- GAJI POKOK -->
                        <div>
                            <label class="text-sm font-medium text-slate-700 mb-2 block">
                                Gaji Pokok <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i
                                    class="fa-solid fa-money-bill-wave absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input id="gaji_pokok" type="number" name="gaji_pokok" required autocomplete="off"
                                    value="{{ old('gaji_pokok', $salary->gaji_pokok ?? '') }}" min="0" max="100000000"
                                    placeholder="Masukkan gaji pokok"
                                    class="w-full h-12 pl-11 pr-4 bg-slate-50 border border-slate-200 rounded-2xl
                                       focus:bg-white focus:border-emerald-500 focus:ring-4
                                       focus:ring-emerald-100 outline-none">
                            </div>
                            @error('gaji_pokok')
                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                    <!-- INFO -->
                    <div class="flex items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <i class="fa-solid fa-circle-info"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-800">
                                Informasi
                            </h4>
                            <p class="text-sm text-slate-500">
                                Pastikan data gaji diisi dengan benar sebelum disimpan.
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
                    <button type="button" onclick="closeAllModal()"
                        class="px-6 py-3 rounded-2xl border border-slate-200 hover:bg-white transition">
                        Batal
                    </button>
                    <button type="submit" form="salaryForm{{ $salary->id }}"
                        class="px-6 py-3 rounded-2xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-lg shadow-emerald-200 transition">
                        <i class="fa-solid fa-floppy-disk mr-2"></i>
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endforeach
