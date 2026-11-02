@php
    $user = auth()->user();
    $role = $user->role;
    $isUser = $role === 'user';
    $isAdminOrSuperAdmin = in_array($role, ['admin', 'super_admin'], true);
    $menuClass = 'flex items-center gap-4 p-4 rounded-2xl transition';
    $activeClass = 'bg-blue-600 text-white shadow-lg shadow-blue-100';
    $inactiveClass = 'text-slate-700 hover:bg-slate-100';
    /*
    |--------------------------------------------------------------------------
    | Permission Admin
    |--------------------------------------------------------------------------
    */
    $permissionCodes = [];
    if ($role === 'admin') {
        $permissionCodes = \App\Models\RolePermission::query()
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('role_permissions.role', 'admin')
            ->pluck('permissions.kode')
            ->all();
    }
    $can = static function (string $permission) use ($role, $permissionCodes): bool {
        return $role === 'super_admin' || ($role === 'admin' && in_array($permission, $permissionCodes, true));
    };
    $canAny = static function (array $permissions) use ($can): bool {
        foreach ($permissions as $permission) {
            if ($can($permission)) {
                return true;
            }
        }
        return false;
    };
    /*
    |--------------------------------------------------------------------------
    | Status Menu
    |--------------------------------------------------------------------------
    */
    $pangkatDropdownActive = request()->routeIs('rank.*') || request()->routeIs('pppk.*');
    $showDataMaster =
        !$isUser &&
        $canAny([
            'user.view',
            'pegawai.view',
            'unit_kerja.view',
            'pangkat.view',
            'pppk.view',
            'jabatan.view',
            'gaji.view',
        ]);
    $bolehLihatLaporanKgb = !$isUser && $can('laporan_kgb.manage');
    $bolehLihatRiwayatKgb = !$isUser && $can('riwayat_kgb.manage');
    $showLaporan = $bolehLihatLaporanKgb || $bolehLihatRiwayatKgb;
    // Pengaturan dapat diakses oleh semua role.
    $showPengaturan = true;
@endphp
<aside id="sidebar"
    class="fixed top-0 left-0 z-50
        w-[80vw] sm:w-[320px] lg:w-72
        h-screen bg-white border-r border-slate-200
        flex flex-col transform transition-transform duration-300
        -translate-x-full lg:translate-x-0">
    {{-- HEADER SIDEBAR --}}
    <div class="p-6 border-b border-slate-100">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-building-columns text-xl"></i>
            <div>
                <h1 class="font-extrabold text-2xl text-slate-800">
                    SIGAP
                </h1>
                <p class="text-sm text-slate-500">
                    Sistem Kepegawaian
                </p>
            </div>
        </div>
    </div>
    {{-- NAVIGATION --}}
    <nav class="flex-1 p-4 overflow-y-auto max-h-[calc(100vh-80px)] sidebar-scroll">
        {{-- ========================================================= --}}
        {{-- MENU KHUSUS USER --}}
        {{-- ========================================================= --}}
        @if ($isUser)
            {{-- DASHBOARD USER --}}
            <p class="text-xs uppercase text-slate-400 px-4 mb-3">
                Menu Utama
            </p>
            <div class="sidebar-menu">
                <a href="{{ route('dashboard_user') }}"
                    class="{{ $menuClass }}
                        {{ request()->routeIs('dashboard_user') ? $activeClass : $inactiveClass }}"
                    onclick="toggleSidebar()">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard User</span>
                </a>
            </div>
            {{-- LAYANAN KEPEGAWAIAN --}}
            <p class="text-xs uppercase text-slate-400 px-4 mt-8 mb-3">
                Layanan Kepegawaian
            </p>
            <div class="sidebar-menu">
                {{-- LAPORAN KGB USER --}}
                <a href="{{ route('report.kgb.user') }}"
                    class="{{ $menuClass }}
                        {{ request()->routeIs('report.kgb.user') ? $activeClass : $inactiveClass }}"
                    onclick="toggleSidebar()">
                    <i class="fa-solid fa-file-lines"></i>
                    <span>Laporan KGB</span>
                </a>
            </div>
        @else
            {{-- ===================================================== --}}
            {{-- MENU ADMIN DAN SUPER ADMIN --}}
            {{-- ===================================================== --}}
            {{-- MENU UTAMA --}}
            @if ($isAdminOrSuperAdmin)
                <p class="text-xs uppercase text-slate-400 px-4 mb-3">
                    Menu Utama
                </p>
                <div class="sidebar-menu">
                    <a href="{{ route('dashboard') }}"
                        class="{{ $menuClass }}
                            {{ request()->routeIs('dashboard') ? $activeClass : $inactiveClass }}"
                        onclick="toggleSidebar()">
                        <i class="fa-solid fa-house"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
            @endif
            {{-- DATA MASTER --}}
            @if ($showDataMaster)
                <p class="text-xs uppercase text-slate-400 px-4 mt-8 mb-3">
                    Data Master
                </p>
                <div class="sidebar-menu">
                    {{-- DATA PENGGUNA --}}
                    @if ($can('user.view'))
                        <a href="{{ route('user.index') }}"
                            class="{{ $menuClass }}
                                {{ request()->routeIs('user.*') ? $activeClass : $inactiveClass }}">
                            <i class="fa-solid fa-users"></i>
                            <span>Data Pengguna</span>
                        </a>
                    @endif
                    {{-- DATA PEGAWAI --}}
                    @if ($can('pegawai.view'))
                        <a href="{{ route('employee.index') }}"
                            class="{{ $menuClass }}
                                {{ request()->routeIs('employee.*') ? $activeClass : $inactiveClass }}">
                            <i class="fa-solid fa-user-tie"></i>
                            <span>Data Pegawai</span>
                        </a>
                    @endif
                    {{-- DATA UNIT KERJA --}}
                    @if ($can('unit_kerja.view'))
                        <a href="{{ route('department.index') }}"
                            class="{{ $menuClass }}
                                {{ request()->routeIs('department.*') ? $activeClass : $inactiveClass }}">
                            <i class="fa-solid fa-landmark"></i>
                            <span>Data Unit Kerja</span>
                        </a>
                    @endif
                    {{-- PANGKAT DAN GOLONGAN --}}
                    @if ($canAny(['pangkat.view', 'pppk.view']))
                        <div class="sidebar-menu-group">
                            <button type="button"
                                class="w-full flex items-center gap-4 p-4 rounded-2xl transition
                                    {{ $pangkatDropdownActive ? $activeClass : $inactiveClass }}"
                                data-sidebar-dropdown-toggle="dropdown-pangkat"
                                aria-expanded="{{ $pangkatDropdownActive ? 'true' : 'false' }}">
                                <i class="fa-solid fa-medal"></i>
                                <span class="flex-1 text-left">
                                    Pangkat &amp; Golongan
                                </span>
                                <i data-dropdown-chevron
                                    class="fa-solid fa-chevron-down text-xs transition-transform duration-200
                                        {{ $pangkatDropdownActive ? 'rotate-180' : '' }}">
                                </i>
                            </button>
                            <div id="dropdown-pangkat" data-sidebar-dropdown
                                class="sidebar-submenu {{ $pangkatDropdownActive ? '' : 'hidden' }}">
                                {{-- PANGKAT PNS --}}
                                @if ($can('pangkat.view'))
                                    <a href="{{ route('rank.index') }}"
                                        class="flex items-center gap-3 w-full px-4 py-3 rounded-xl transition text-sm
                                            {{ request()->routeIs('rank.*') ? $activeClass : $inactiveClass }}">
                                        <i class="fa-solid fa-medal w-5 text-center"></i>
                                        <span>Pangkat PNS</span>
                                    </a>
                                @endif
                                {{-- GOLONGAN PPPK --}}
                                @if ($can('pppk.view'))
                                    <a href="{{ route('pppk.index') }}"
                                        class="flex items-center gap-3 w-full px-4 py-3 rounded-xl transition text-sm
                                            {{ request()->routeIs('pppk.*') ? $activeClass : $inactiveClass }}">
                                        <i class="fa-solid fa-layer-group w-5 text-center"></i>
                                        <span>Golongan PPPK</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                    {{-- DATA JABATAN --}}
                    @if ($can('jabatan.view'))
                        <a href="{{ route('position.index') }}"
                            class="{{ $menuClass }}
                                {{ request()->routeIs('position.*') ? $activeClass : $inactiveClass }}">
                            <i class="fa-solid fa-briefcase"></i>
                            <span>Data Jabatan</span>
                        </a>
                    @endif
                    {{-- DATA GAJI --}}
                    @if ($can('gaji.view'))
                        <a href="{{ route('salary.index') }}"
                            class="{{ $menuClass }}
                                {{ request()->routeIs('salary.*') ? $activeClass : $inactiveClass }}">
                            <i class="fa-solid fa-money-bill-wave"></i>
                            <span>Data Gaji</span>
                        </a>
                    @endif
                    {{-- MANAJEMEN HAK AKSES: SUPER ADMIN --}}
                    @if ($role === 'super_admin')
                        <a href="{{ route('setting.role-permissions.index') }}"
                            class="{{ $menuClass }}
                                {{ request()->routeIs('setting.role-permissions.*') ? $activeClass : $inactiveClass }}"
                            onclick="toggleSidebar()">
                            <i class="fa-solid fa-user-shield"></i>
                            <span>Manajemen Hak Akses</span>
                        </a>
                    @endif
                </div>
            @endif
            {{-- LAPORAN ADMIN DAN SUPER ADMIN --}}
            @if ($showLaporan)
                <p class="text-xs uppercase text-slate-400 px-4 mt-8 mb-3">
                    Laporan
                </p>
                <div class="sidebar-menu">
                    {{-- LAPORAN KGB --}}
                    @if ($bolehLihatLaporanKgb)
                        <a href="{{ route('report.kgb') }}"
                            class="{{ $menuClass }}
                                {{ request()->routeIs('report.kgb') ? $activeClass : $inactiveClass }}">
                            <i class="fa-solid fa-file-lines"></i>
                            <span>Laporan KGB</span>
                        </a>
                    @endif
                    {{-- RIWAYAT LAPORAN KGB --}}
                    @if ($bolehLihatRiwayatKgb)
                        <a href="{{ route('history.kgb') }}"
                            class="{{ $menuClass }}
                                {{ request()->routeIs('history.kgb') ? $activeClass : $inactiveClass }}">
                            <i class="fa-solid fa-file-circle-check"></i>
                            <span>Riwayat Laporan KGB</span>
                        </a>
                    @endif
                </div>
            @endif
        @endif
        {{-- ========================================================= --}}
        {{-- PENGATURAN --}}
        {{-- ========================================================= --}}
        @if ($showPengaturan)
            <p class="text-xs uppercase text-slate-400 px-4 mt-8 mb-3">
                Pengaturan
            </p>
            <div class="sidebar-menu">
                {{-- RIWAYAT AKTIVITAS --}}

                <a href="{{ $isUser ? route('activityLog.user') : route('activityLog.index') }}"
                    class="{{ $menuClass }}
                            {{ request()->routeIs('activityLog.*') ? $activeClass : $inactiveClass }}"
                    onclick="toggleSidebar()">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Riwayat Aktivitas</span>
                </a>

                {{-- PENGATURAN AKUN --}}

                <a href="{{ $isUser ? route('setting.index') : route('setting.index') }}"
                    class="{{ $menuClass }}
                            {{ request()->routeIs('setting.index', 'setting.user.*') ? $activeClass : $inactiveClass }}"
                    onclick="toggleSidebar()">
                    <i class="fa-solid fa-gear"></i>
                    <span>Pengaturan</span>
                </a>

                {{-- KELUAR --}}
                <button type="button" onclick="openModal('modalLogout')"
                    class="w-full flex items-center gap-4 px-4 py-3 rounded-xl
                        hover:bg-red-50 hover:text-red-600 transition text-left">
                    <i class="fa-solid fa-right-from-bracket text-xl w-6"></i>
                    <span class="font-medium">Keluar</span>
                </button>
            </div>
        @endif
    </nav>
</aside>
