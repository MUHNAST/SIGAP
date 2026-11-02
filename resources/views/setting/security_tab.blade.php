<div id="security-tab" class="hidden p-6">
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Ubah Password --}}
        <div class="xl:col-span-2">
            <div class="bg-white border border-slate-200 rounded-2xl p-6 h-full">
                <h3 class="text-xl font-semibold mb-2">
                    Ubah Password
                </h3>
                <p class="text-slate-500 text-sm mb-6">
                    Perbarui password akun Anda untuk menjaga keamanan akun.
                </p>
                <form action="{{ route('setting.password.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="active_tab" class="active-tab-input"
                        value="{{ old('active_tab', session('active_tab', 'security')) }}">
                    <div class="space-y-5">
                        {{-- Password Lama --}}
                        <div>
                            <label for="current_password" class="block text-sm font-medium mb-2">
                                Password Lama
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="password" id="current_password" name="current_password"
                                    autocomplete="current-password" required
                                    class="w-full pl-11 pr-12 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('current_password') border-red-500 @enderror">
                                <button type="button" onclick="togglePassword('current_password', this)"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600"
                                    aria-label="Tampilkan password lama">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            @error('current_password')
                                <small class="text-red-500">{{ $message }}</small>
                            @enderror
                        </div>
                        {{-- Password Baru --}}
                        <div>
                            <label for="password" class="block text-sm font-medium mb-2">
                                Password Baru
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="password" id="password" name="password" autocomplete="new-password"
                                    required
                                    class="w-full pl-11 pr-12 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password') border-red-500 @enderror">
                                <button type="button" onclick="togglePassword('password', this)"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600"
                                    aria-label="Tampilkan password baru">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <small class="text-red-500">{{ $message }}</small>
                            @enderror
                        </div>
                        {{-- Konfirmasi Password --}}
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium mb-2">
                                Konfirmasi Password Baru
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    autocomplete="new-password" required
                                    class="w-full pl-11 pr-12 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button type="button" onclick="togglePassword('password_confirmation', this)"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600"
                                    aria-label="Tampilkan konfirmasi password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl transition">
                            <i class="fa-solid fa-key mr-2"></i>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
        {{-- Sidebar Keamanan --}}
        <div class="space-y-6">
            {{-- Pengaturan Two-Factor Authentication --}}
            {{-- Pengaturan Two-Factor Authentication --}}
            <div class="bg-white border border-slate-200 rounded-2xl p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold mb-2">
                            Verifikasi Dua Langkah (2FA)
                        </h3>
                        <p class="text-sm text-slate-500">
                            Lindungi akun dengan kode OTP yang dikirim ke email saat login.
                        </p>
                    </div>
                    @if ($user->email_2fa_enabled)
                        <span
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-green-50 text-green-700 text-sm font-medium whitespace-nowrap">
                            <i class="fa-solid fa-shield-halved"></i>
                            Aktif
                        </span>
                    @else
                        <span
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-100 text-slate-600 text-sm font-medium whitespace-nowrap">
                            <i class="fa-solid fa-shield"></i>
                            Nonaktif
                        </span>
                    @endif
                </div>
                <div class="border-t border-slate-100 my-5"></div>
                @if ($user->email_2fa_enabled)
                    {{-- Nonaktifkan 2FA --}}
                    <p class="text-sm text-slate-600 mb-4">
                        Verifikasi dua langkah aktif. Kode OTP akan dikirim ke email
                        {{ $user->email }} setiap kali login.
                    </p>
                    <form action="{{ route('setting.two-factor.disable') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('DELETE')
                        <input type="text" name="username" value="{{ $user->username }}" autocomplete="username"
                            hidden>
                        <div>
                            <label for="disable_2fa_password" class="block text-sm font-medium mb-2">
                                Password Saat Ini
                            </label>
                            <div class="relative">
                                <input type="password" id="disable_2fa_password" name="current_password"
                                    autocomplete="current-password" required
                                    class="w-full border border-slate-200 rounded-xl px-4 py-3 pr-12 focus:outline-none focus:ring-2 focus:ring-red-500">
                                <button type="button" onclick="togglePassword('disable_2fa_password', this)"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600"
                                    aria-label="Tampilkan atau sembunyikan password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            @error('current_password')
                                <small class="text-red-500">{{ $message }}</small>
                            @enderror
                        </div>
                        <button type="submit"
                            class="w-full bg-red-600 hover:bg-red-700 text-white px-5 py-3 rounded-xl transition">
                            <i class="fa-solid fa-shield-halved mr-2"></i>
                            Nonaktifkan 2FA
                        </button>
                    </form>
                @else
                    {{-- Aktifkan 2FA --}}
                    <p class="text-sm text-slate-600 mb-4">
                        2FA saat ini nonaktif. Aktifkan agar sistem meminta kode OTP
                        yang dikirim ke email akun setiap kali login.
                    </p>
                    <div class="mb-4 rounded-xl border border-blue-200 bg-blue-50 p-4">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-envelope text-blue-600 mt-1"></i>
                            <div class="text-sm text-blue-800">
                                <p class="font-semibold">Email verifikasi</p>
                                <p class="mt-1">
                                    {{ $user->email ?: 'Email belum tersedia.' }}
                                </p>
                                @if (blank($user->email))
                                    <p class="mt-2 text-red-600">
                                        Lengkapi email akun terlebih dahulu sebelum mengaktifkan 2FA.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('setting.two-factor.enable') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="text" name="username" value="{{ $user->username }}" autocomplete="username"
                            hidden>
                        <div>
                            <label for="enable_2fa_password" class="block text-sm font-medium mb-2">
                                Password Saat Ini
                            </label>
                            <div class="relative">
                                <input type="password" id="enable_2fa_password" name="current_password"
                                    autocomplete="current-password" required
                                    class="w-full border border-slate-200 rounded-xl px-4 py-3 pr-12 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button type="button" onclick="togglePassword('enable_2fa_password', this)"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600"
                                    aria-label="Tampilkan atau sembunyikan password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            @error('current_password')
                                <small class="text-red-500">{{ $message }}</small>
                            @enderror
                        </div>
                        <button type="submit" @disabled(blank($user->email))
                            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white px-5 py-3 rounded-xl transition">
                            <i class="fa-solid fa-shield-halved mr-2"></i>
                            Aktifkan 2FA
                        </button>
                    </form>
                @endif
            </div>
            {{-- Login Terakhir --}}
            <div class="bg-white border border-slate-200 rounded-2xl p-6">
                <h3 class="font-semibold text-lg mb-4">
                    Login Terakhir
                </h3>
                @if ($lastLogin)
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-slate-500">Waktu</p>
                            <p class="font-medium">
                                {{ $lastLogin->created_at->timezone('Asia/Jakarta')->format('d F Y, H:i') }} WIB
                            </p>
                        </div>
                        <div>
                            <p class="text-slate-500">IP Address</p>
                            <p class="font-medium">
                                {{ $lastLogin->ip_address }}
                            </p>
                        </div>
                        <div>
                            <p class="text-slate-500">Perangkat</p>
                            <p class="font-medium">
                                {{ Str::limit($lastLogin->user_agent, 50) }}
                            </p>
                        </div>
                    </div>
                @else
                    <p class="text-slate-500">
                        Belum ada riwayat login.
                    </p>
                @endif
            </div>
            {{-- Logout Semua Perangkat --}}
            <div class="bg-white border border-slate-200 rounded-2xl p-6">
                <h3 class="font-semibold text-lg mb-4">
                    Sesi Aktif
                </h3>
                <p class="text-slate-500 text-sm mb-4">
                    Logout dari seluruh perangkat yang sedang aktif.
                </p>
                <button type="button" onclick="openModal('modalLogoutDevices')"
                    class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-xl transition">
                    <i class="fa-solid fa-right-from-bracket mr-2"></i>
                    Logout Semua Perangkat
                </button>
            </div>
        </div>
    </div>
    {{-- Riwayat Aktivitas --}}
    <div class="mt-6">
        <div class="bg-white border border-slate-200 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold">
                    Riwayat Aktivitas
                </h3>
                <a href="{{ route('activityLog.index') }}"
                    class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                    Lihat Semua
                    <i class="fa-solid fa-arrow-right ml-1"></i>
                </a>
            </div>
            <x-activity-card :logs="$activities" />
        </div>
    </div>
</div>
