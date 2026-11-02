<!-- Modal Tentang Aplikasi -->
<div id="aboutModal" tabindex="-1" aria-hidden="true"
    class="hidden fixed inset-0 z-[100] flex items-center justify-center w-full h-full bg-black/50 p-4">
    <div class="relative w-full max-w-3xl max-h-[90vh] flex flex-col">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="shrink-0 px-6 py-5 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 flex items-center justify-center">
                        <i class="fa-solid fa-circle-info text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">
                            Tentang Aplikasi
                        </h3>
                        <p class="text-sm text-slate-500">
                            Sistem Informasi Kepegawaian
                        </p>
                    </div>
                </div>
                <button type="button" data-modal-hide="aboutModal"
                    class="text-slate-400 hover:text-red-500 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <!-- Body Scroll -->
            <div class="p-6 space-y-5 overflow-y-auto">
                <!-- Logo -->
                <div class="text-center">
                    <div class="mx-auto w-20 h-20 rounded-3xl bg-blue-100 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-users-gear text-blue-600 text-4xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-800">
                        SIGAP
                    </h2>
                    <p class="text-slate-500">
                        Sistem Informasi Kepegawaian
                    </p>
                </div>
                <!-- Informasi -->
                <div class="grid grid-cols-1 gap-3">
                    <div class="flex justify-between bg-slate-50 rounded-xl px-4 py-3">
                        <span class="text-slate-500">
                            Versi
                        </span>
                        <span class="font-semibold text-slate-800">
                            1.0.0
                        </span>
                    </div>
                    <div class="flex justify-between bg-slate-50 rounded-xl px-4 py-3">
                        <span class="text-slate-500">
                            Developer
                        </span>
                        <span class="font-semibold text-slate-800">
                            Muhammad Nastain
                        </span>
                    </div>
                    <div class="flex justify-between bg-slate-50 rounded-xl px-4 py-3">
                        <span class="text-slate-500">
                            Copyright
                        </span>
                        <span class="font-semibold text-slate-800">
                            © {{ date('Y') }}
                        </span>
                    </div>
                    <div class="flex justify-between bg-slate-50 rounded-xl px-4 py-3">
                        <span class="text-slate-500">
                            Update Terakhir
                        </span>
                        <span class="font-semibold text-slate-800">
                            {{ now()->translatedFormat('d F Y') }}
                        </span>
                    </div>
                </div>
                <!-- Deskripsi -->
                <div class="rounded-2xl bg-blue-50 border border-blue-100 p-4">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-shield-halved text-blue-600 mt-1"></i>
                        <div>
                            <h4 class="font-semibold text-slate-800">
                                Tentang SIGAP
                            </h4>
                            <p class="text-sm text-slate-600 mt-1 leading-6">
                                SIGAP merupakan aplikasi yang digunakan untuk membantu pengelolaan data kepegawaian,
                                kenaikan gaji berkala (KGB), kenaikan pangkat, notifikasi, serta penyusunan laporan
                                administrasi secara terintegrasi di lingkungan instansi.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <div class="shrink-0 px-6 py-4 border-t border-slate-200 flex justify-end bg-white">
                <button type="button" data-modal-hide="aboutModal"
                    class="px-5 py-2.5 rounded-xl bg-slate-800 text-white hover:bg-slate-900 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
