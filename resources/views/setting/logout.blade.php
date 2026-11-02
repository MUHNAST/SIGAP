<div id="modalLogoutDevices" role="dialog" aria-modal="true"
    class=" modal fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-3xl overflow-hidden shadow-[0_20px_60px_rgba(0,0,0,.15)]">
        <!-- HEADER -->
        <div class="bg-gradient-to-r from-red-600 to-rose-600 px-6 py-5 relative">
            <div class="absolute right-0 top-0 opacity-10">
                <i class="fa-solid fa-right-from-bracket text-[110px]"></i>
            </div>
            <div class="relative flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center text-white text-xl">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white">
                        Logout Semua Perangkat
                    </h2>
                    <p class="text-red-100 text-sm">
                        Konfirmasi keamanan akun
                    </p>
                </div>
            </div>
        </div>
        <!-- BODY -->
        <div class="p-6">
            <div
                class="w-20 h-20 mx-auto rounded-full bg-red-100 flex items-center justify-center text-red-600 text-3xl mb-5">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="text-center text-lg font-bold mb-2">
                Logout dari semua perangkat?
            </h3>
            <p class="text-center text-slate-500 mb-6">
                Semua sesi login selain perangkat yang sedang digunakan akan dikeluarkan.
                Masukkan password untuk melanjutkan.
            </p>
            <form action="{{ route('setting.logout.devices') }}" method="POST">
                @csrf
                <input type="hidden" name="active_tab" value="security">
                <input type="text" value="{{ auth()->user()->username }}" autocomplete="username" tabindex="-1"
                    aria-hidden="true" class="absolute opacity-0 w-0 h-0 pointer-events-none">
                <div>
                    <label class="block text-sm font-medium mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input id="logout_password" type="password" name="current_password"
                            autocomplete="current-password" required
                            class="w-full pl-11 pr-12 border border-slate-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-red-500">
                        <button type="button" onclick="togglePassword('logout_password',this)"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-600">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    @error('current_password')
                        <small class="text-red-500">
                            {{ $message }}
                        </small>
                    @enderror
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeAllModal()"
                        class="px-5 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-100">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white">
                        <i class="fa-solid fa-right-from-bracket mr-2"></i>
                        Logout
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
