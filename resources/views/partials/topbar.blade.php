<header class="sticky top-0 z-[60] bg-white border-b border-slate-200 px-4 md:px-8 py-4">
    <div class="flex items-center justify-between gap-3">
        <!-- LEFT -->
        <div class="flex items-center gap-3 md:gap-4 min-w-0">
            <!-- HAMBURGER BUTTON -->
            <button type="button" onclick="toggleSidebar()"
                class="relative z-[60] w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-100">
                <i class="fa-solid fa-bars"></i>
            </button>
            <!-- ICON -->
            <div
                class="w-10 md:w-12 h-10 md:h-12 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                <i class="fa-solid fa-chart-pie text-lg md:text-xl"></i>
            </div>
            <!-- TITLE -->
            <div class="min-w-0">
                <h2 class="text-lg md:text-2xl font-bold text-slate-800 truncate">
                    {{$title ?? $data['title']}}
                </h2>
                <p class="text-xs md:text-sm text-slate-500 truncate">
                {{ $sub_title ?? ($data['deskripsi'] ?? '') }}
                </p>
            </div>
        </div>
        <!-- RIGHT -->
        <div class="flex items-center gap-2 md:gap-5">
            <!-- NOTIFICATION -->
            <div class="relative">
                <!-- BUTTON -->
                <button type="button" onclick="toggleNotif(event)"
                    class="relative w-11 h-11 rounded-full border border-slate-200 hover:border-slate-400 hover:bg-slate-50 flex items-center justify-center transition"
                    aria-label="Notifikasi">
                    <i class="fa-regular fa-bell text-xl text-slate-700"></i>
                    @if ($unreadCount > 0)
                        <span
                            class="absolute top-2 right-2 min-w-[18px] h-[18px] px-1 bg-orange-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center ring-2 ring-white animate-pulse">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </button>
                <!-- DROPDOWN -->
                <div id="notif"
                    class="hidden absolute right-0 mt-4 w-[430px] bg-white border border-slate-200 rounded-3xl shadow-2xl overflow-hidden origin-top-right opacity-0 scale-95 transition-all duration-200 ease-out z-50">
                    <!-- HEADER -->
                    <div class="flex items-center justify-between px-5 py-5 border-b border-slate-200">
                        <h2 class="text-[28px] font-bold text-slate-800">
                            Notification
                        </h2>
                        <button type="button" onclick="toggleNotif(event)"
                            class="text-slate-400 hover:text-slate-700 transition-colors duration-200"
                            aria-label="Tutup notifikasi">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>
                    <!-- CONTENT -->
                    <div class="max-h-[430px] overflow-y-auto px-4 py-3 space-y-2">
                        @forelse ($topNotifications as $notification)
                            @php
                                $icon = 'fa-bell';
                                $bg = 'bg-slate-100';
                                $text = 'text-slate-600';
                                switch (strtolower($notification->jenis)) {
                                    case 'pangkat':
                                        $icon = 'fa-chart-line';
                                        $bg = 'bg-orange-100';
                                        $text = 'text-orange-600';
                                        break;
                                    case 'kgb':
                                        $icon = 'fa-wallet';
                                        $bg = 'bg-blue-100';
                                        $text = 'text-blue-600';
                                        break;
                                    case 'pensiun':
                                        $icon = 'fa-user-clock';
                                        $bg = 'bg-green-100';
                                        $text = 'text-green-600';
                                        break;
                                }
                            @endphp
                            <a href="{{ route('notification.read.notification', $notification->id) }}"
                                class="flex items-start gap-4 p-4 rounded-2xl border transition
                                {{ !$notification->sudah_dibaca ? 'bg-orange-50 border-orange-200' : 'bg-white border-slate-100 hover:bg-slate-50' }}">
                                <!-- ICON -->
                                <div class="relative flex-shrink-0">
                                    <div
                                        class="w-14 h-14 rounded-full {{ $bg }} flex items-center justify-center">
                                        <i class="fa-solid {{ $icon }} {{ $text }} text-lg"></i>
                                    </div>
                                    @if (!$notification->sudah_dibaca)
                                        <span
                                            class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-red-500 border-2 border-white rounded-full"></span>
                                    @endif
                                </div>
                                <!-- CONTENT -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-[15px] leading-7 text-slate-500">
                                        <span class="font-bold text-slate-800">
                                            {{ $notification->judul }}
                                        </span>
                                        {{ Str::limit($notification->pesan, 70) }}
                                    </p>
                                    <p class="text-sm text-slate-400 mt-1">
                                        {{ $notification->nama ?? 'Admin' }}
                                        &nbsp; • &nbsp;
                                        {{ $notification->created_at?->diffForHumans() }}
                                    </p>
                                </div>
                            </a>
                        @empty
                            <div class="py-10 text-center">
                                <div
                                    class="w-16 h-16 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-4">
                                    <i class="fa-regular fa-bell-slash text-2xl text-slate-400"></i>
                                </div>
                                <h3 class="font-semibold text-slate-700">
                                    Tidak Ada Notifikasi
                                </h3>
                                <p class="text-sm text-slate-500 mt-2">
                                    Belum ada notifikasi terbaru.
                                </p>
                            </div>
                        @endforelse
                    </div>
                    <!-- FOOTER -->
                    <div class="p-5 border-t border-slate-200 bg-white">
                        <a href="{{ route('notification.index') }}"
                            class="block w-full rounded-xl border border-slate-300 py-4 text-center font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Lihat Semua Notifikasi
                        </a>
                    </div>
                </div>
            </div>
            <!-- USER PROFILE -->
            <div class="relative">
                @php
                    $foto = $user->foto;
                    $fotoUrl =
                        !$foto || $foto === 'images/default-user.png'
                            ? asset('images/default-user.png')
                            : asset('storage/' . $foto);
                @endphp
                <!-- BUTTON -->
                <button type="button" onclick="toggleUser(event)"
                    class="flex items-center gap-3 bg-white border border-slate-300 hover:border-slate-500 rounded-xl px-3 py-2 transition duration-200"
                    aria-label="Menu pengguna">
                    <img src="{{ $fotoUrl }}" alt="Foto {{ $user->nama ?? 'Pengguna' }}"
                        class="w-12 h-12 rounded-full object-cover">
                    <div class="hidden md:block text-left">
                        <h4 class="text-sm font-semibold text-slate-800">
                            {{ $user->nama ?? 'Pengguna' }}
                        </h4>
                        <p class="text-xs text-slate-500">
                            {{ ucwords(str_replace('_', ' ', $user->role ?? 'User')) }}
                        </p>
                    </div>
                    <i class="fa-solid fa-chevron-down text-xs text-slate-500"></i>
                </button>
                <!-- USER DROPDOWN -->
                <div id="user"
                    class="hidden absolute right-0 mt-3 w-80 bg-white border border-slate-200 rounded-2xl shadow-[0_15px_35px_rgba(15,23,42,.15)] overflow-hidden z-50 origin-top-right opacity-0 scale-95 transition-all duration-200 ease-out">
                    <!-- HEADER -->
                    <div class="p-5">
                        <div class="flex items-center gap-3">
                            <img src="{{ $fotoUrl }}" alt="Foto {{ $user->nama ?? 'Pengguna' }}"
                                class="w-12 h-12 rounded-full object-cover">
                            <div>
                                <h3 class="font-semibold text-slate-800">
                                    {{ $user->nama ?? 'Pengguna' }}
                                </h3>
                                <p class="text-sm text-slate-500">
                                    {{ ucwords(str_replace('_', ' ', $user->role ?? 'User')) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- MENU -->
                    <div class="px-2 pb-3">
                        <!-- PENGATURAN -->
                        <a href="{{ route('setting.index') }}"
                            class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-slate-100 transition">
                            <i class="fa-solid fa-gear text-xl w-6 text-slate-500"></i>
                            <span class="font-medium text-slate-700">
                                Pengaturan
                            </span>
                        </a>
                        <!-- TENTANG APLIKASI -->
                        <button type="button" data-modal-target="aboutModal" data-modal-toggle="aboutModal"
                            class="flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-slate-100 transition w-full text-left">
                            <i class="fa-regular fa-circle-question text-xl w-6 text-slate-500"></i>
                            <span class="font-medium text-slate-700">
                                Tentang Aplikasi
                            </span>
                        </button>
                        <hr class="my-2 border-slate-200">
                        <!-- KELUAR -->
                        <button type="button" onclick="openModal('modalLogout')"
                            class="w-full flex items-center gap-4 px-4 py-3 rounded-xl hover:bg-red-50 hover:text-red-600 transition text-left">
                            <i class="fa-solid fa-right-from-bracket text-xl w-6"></i>
                            <span class="font-medium">
                                Keluar
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
