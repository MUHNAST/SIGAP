<div id="modalLogout" role="dialog" aria-modal="true"
    class="modal fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div
        class="bg-white w-full max-w-md rounded-3xl shadow-[0_20px_60px_rgba(0,0,0,0.15)] overflow-hidden animate-[modalShow_.25s_ease]">
        <!-- HEADER -->
        <div class="bg-gradient-to-r from-orange-500 to-red-500 px-6 py-5 relative">
            <div class="absolute right-0 top-0 opacity-10">
                <i class="fa-solid fa-right-from-bracket text-[120px]"></i>
            </div>
            <div class="relative flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-white text-xl">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white">
                        Logout
                    </h2>
                    <p class="text-orange-100 text-sm">
                        Konfirmasi keluar dari sistem
                    </p>
                </div>
            </div>
        </div>
        <!-- BODY -->
        <div class="p-6">
            <div
                class="w-20 h-20 mx-auto rounded-full bg-orange-100 flex items-center justify-center text-orange-600 text-3xl mb-5">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <h3 class="text-center text-lg font-bold text-slate-800 mb-2">
                Yakin ingin keluar?
            </h3>
            <p class="text-center text-slate-500">
                Anda akan keluar dari aplikasi SIGAP dan harus login kembali
                untuk mengakses dashboard.
            </p>
        </div>
        <!-- FOOTER -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
            <button type="button" onclick="closeAllModal()"
                class="px-5 py-2.5 rounded-xl border border-slate-200 hover:bg-white transition">
                Batal
            </button>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-orange-600 text-white hover:bg-orange-700 shadow-lg shadow-orange-200 transition">
                    <i class="fa-solid fa-right-from-bracket mr-2"></i>
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>
