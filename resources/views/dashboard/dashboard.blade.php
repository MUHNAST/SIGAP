<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="overflow-x-hidden">
    <div class="flex">
        <main id="mainContent" class="w-full min-w-0 flex-1 ml-0 lg:ml-72 transition-all duration-300">
            <div class="p-4 md:p-6 lg:p-8">
                <!-- WELCOME -->
                @if (session('show_welcome'))
                    <div id="welcomeCard"
                        class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-3xl p-8 text-white mb-8 shadow-xl transition-all duration-500">
                        <h1 class="text-3xl font-bold">
                            {{ session('welcome_message') }} 👋
                        </h1>
                        <p class="mt-2 opacity-90">
                            Pantau seluruh aktivitas kepegawaian dalam satu dashboard.
                        </p>
                    </div>
                @endif
                <!-- STATISTIC -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-4 md:gap-6 mb-8">
                    {{-- Total Pegawai --}}
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                        <div class="flex items-start justify-between gap-4">
                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-500">
                                    Total Pegawai
                                </p>
                                <h2 class="mt-3 text-4xl font-bold leading-none text-slate-800">
                                    {{ number_format($pegawaiAktif) }}
                                </h2>
                                <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-slate-500">
                                    <span>
                                        <span class="font-semibold text-green-600">
                                            {{ $pegawaiAktif }}
                                        </span>
                                        Aktif
                                    </span>
                                    <span class="text-slate-300">•</span>
                                    <span>
                                        <span class="font-semibold text-orange-500">
                                            {{ $pegawaiNonaktif }}
                                        </span>
                                        Nonaktif
                                    </span>
                                    <span class="text-slate-300">•</span>
                                    <span>
                                        <span class="font-semibold text-red-500">
                                            {{ $pegawaiPensiun }}
                                        </span>
                                        Pensiun
                                    </span>
                                </div>
                            </div>
                            {{-- Icon --}}
                            <div
                                class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-600">
                                <i class="fa-solid fa-users text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    {{-- Naik Pangkat --}}
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-slate-500 text-sm">
                                    Naik Pangkat
                                </p>
                                <h2 class="text-3xl lg:text-4xl font-bold mt-3 text-slate-800">
                                    {{ $naikPangkat }}
                                </h2>
                                <div class="mt-3 text-sm text-green-600 font-medium">
                                    Dalam 2 bulan ke depan
                                </div>
                            </div>
                            <div
                                class="w-16 h-16 rounded-2xl bg-green-100 text-green-600 flex items-center justify-center">
                                <i class="fa-solid fa-arrow-trend-up text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    {{-- KGB --}}
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-slate-500 text-sm">
                                    KGB
                                </p>
                                <h2 class="text-3xl lg:text-4xl font-bold mt-3 text-slate-800">
                                    {{ $kgb }}
                                </h2>
                                <div class="mt-3 text-sm text-purple-600 font-medium">
                                    Dalam 2 bulan ke depan
                                </div>
                            </div>
                            <div
                                class="w-16 h-16 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center">
                                <i class="fa-solid fa-money-bill-wave text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    {{-- Pensiun --}}
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-slate-500 text-sm">
                                    Pensiun
                                </p>
                                <h2 class="text-3xl lg:text-4xl font-bold mt-3 text-slate-800">
                                    {{ $pensiun }}
                                </h2>
                                <div class="mt-3 text-sm text-orange-600 font-medium">
                                    Dalam 6 bulan ke depan
                                </div>
                            </div>
                            <div
                                class="w-16 h-16 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center">
                                <i class="fa-solid fa-user-clock text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- MIDDLE -->
                <div class="space-y-6">
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 mb-6">
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-slate-800">
                                Statistik Pegawai
                            </h2>
                            <p class="text-sm text-slate-500 mt-1">
                                Ringkasan statistik pegawai berdasarkan jenis pegawai, jenis kelamin, status, dan
                                pendidikan.
                            </p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                            {{-- Jenis Pegawai --}}
                            <div class="rounded-2xl border border-slate-100 p-5">
                                <h4 class="text-center font-semibold text-slate-700 mb-5">
                                    Jenis Pegawai
                                </h4>
                                <div class="relative h-64 sm:h-72">
                                    <canvas id="chartJenisPegawai"></canvas>
                                </div>
                                <div id="legendJenisPegawai" class="mt-6 space-y-2"></div>
                            </div>
                            {{-- Jenis Kelamin --}}
                            <div class="rounded-2xl border border-slate-100 p-5">
                                <h4 class="text-center font-semibold text-slate-700 mb-5">
                                    Jenis Kelamin
                                </h4>
                                <div class="relative h-64 sm:h-72">
                                    <canvas id="ChartJenisKelamin"></canvas>
                                </div>
                                <div id="legendGender" class="mt-6 space-y-2"></div>
                            </div>
                            {{-- Status Pegawai --}}
                            <div class="rounded-2xl border border-slate-100 p-5">
                                <h4 class="text-center font-semibold text-slate-700 mb-5">
                                    Jumlah Pegawai
                                </h4>
                                <div class="relative h-64 sm:h-72">
                                    <canvas id="chartStatus"></canvas>
                                </div>
                                <div id="legendStatus" class="mt-6 space-y-2"></div>
                            </div>
                            {{-- Pendidikan --}}
                            <div class="rounded-2xl border border-slate-100 p-5">
                                <h4 class="text-center font-semibold text-slate-700 mb-5">
                                    Pendidikan
                                </h4>
                                <div class="relative h-64 sm:h-72">
                                    <canvas id="chartPendidikan"></canvas>
                                </div>
                                <div id="legendPendidikan" class="mt-6 space-y-2"></div>
                            </div>
                        </div>
                    </div>
                    {{-- ================================================= --}}
                    {{-- ANALISIS KEPEGAWAIAN --}}
                    {{-- ================================================= --}}
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 mb-6">
                        <div class="mb-8">
                            <h2 class="text-xl font-bold text-slate-800">
                                Analisis Kepegawaian
                            </h2>
                            <p class="text-sm text-slate-500 mt-1">
                                Distribusi pangkat, masa kerja, usia, agama, dan status pernikahan.
                            </p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            {{-- Distribusi Pangkat PNS --}}
                            <div class="rounded-2xl border border-slate-100 p-5">
                                <h4 class="font-semibold text-slate-700 mb-5">
                                    Distribusi Pangkat PNS
                                </h4>

                                <div class="h-80">
                                    <canvas id="chartPangkat"></canvas>
                                </div>
                            </div>

                            {{-- Distribusi Golongan PPPK --}}
                            <div class="rounded-2xl border border-slate-100 p-5">
                                <h4 class="font-semibold text-slate-700 mb-5">
                                    Distribusi Golongan PPPK
                                </h4>

                                <div class="h-80">
                                    <canvas id="chartPangkatPppk"></canvas>
                                </div>
                            </div>
                            {{-- Masa Kerja --}}
                            <div class="rounded-2xl border border-slate-100 p-5">
                                <h4 class="font-semibold text-slate-700 mb-5">
                                    Masa Kerja
                                </h4>
                                <div class="h-80">
                                    <canvas id="chartMasaKerja"></canvas>
                                </div>
                                <div id="legendMasaKerja" class="mt-6 space-y-2"></div>
                            </div>
                            {{-- Kelompok Usia --}}
                            <div class="rounded-2xl border border-slate-100 p-5">
                                <h4 class="font-semibold text-slate-700 mb-5">
                                    Kelompok Usia
                                </h4>
                                <div class="h-80">
                                    <canvas id="chartUsia"></canvas>
                                </div>
                                <div id="legendUsia" class="mt-6 space-y-2"></div>
                            </div>
                        </div>
                    </div>
                    {{-- ================================================= --}}
                    {{-- REMINDER KEPEGAWAIAN --}}
                    {{-- ================================================= --}}
                    {{-- <div class="grid grid-cols-1 lg:grid-cols-2 gap-6"> --}}
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                            <div
                                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 border-b border-slate-100">
                                <div>
                                    <h4 class="text-lg font-semibold text-slate-800">
                                        Reminder Kepegawaian
                                    </h4>
                                    <p class="text-sm text-slate-500">
                                        Statistik reminder pegawai berdasarkan tahun.
                                    </p>
                                </div>
                                <form method="GET">
                                    <input type="hidden" name="riwayat_year" value="{{ $tahunRiwayat }}">
                                    <select name="reminder_year" onchange="this.form.submit()"
                                        class="w-full sm:w-40 rounded-xl border-slate-300 focus:ring-emerald-500 focus:border-emerald-500">
                                        @foreach ($tahunList as $tahun)
                                            <option value="{{ $tahun }}"
                                                {{ $tahun == $tahunReminder ? 'selected' : '' }}>
                                                {{ $tahun }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                            <div class="p-5">
                                <div class="h-72">
                                    <canvas id="pegawaiChart"></canvas>
                                </div>
                                <div class="text-center text-xs text-slate-500 mt-5">
                                    <span class="font-semibold text-slate-700">
                                        {{ $chartReminder['pegawai_reminder'] }}
                                    </span>
                                    Pegawai
                                    <span class="mx-2">•</span>
                                    <span class="font-semibold text-slate-700">
                                        {{ $chartReminder['total_event'] }}
                                    </span>
                                    Event
                                </div>
                                <div id="legendReminder" class="mt-6 space-y-2">
                                </div>
                            </div>
                        </div>
                        {{-- ================================================= --}}
                        {{-- Riwayat Kepegawaian --}}
                        {{-- ================================================= --}}
                        {{-- <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                            <div
                                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 border-b border-slate-100">
                                <div>
                                    <h4 class="text-lg font-semibold text-slate-800">
                                        Riwayat Kepegawaian
                                    </h4>
                                    <p class="text-sm text-slate-500">
                                        Statistik riwayat pegawai berdasarkan tahun.
                                    </p>
                                </div>
                                <form method="GET">
                                    <input type="hidden" name="reminder_year" value="{{ $tahunReminder }}">
                                    <select name="riwayat_year" onchange="this.form.submit()"
                                        class="w-full sm:w-40 rounded-xl border-slate-300 focus:ring-emerald-500 focus:border-emerald-500">
                                        @foreach ($tahunList as $tahun)
                                            <option value="{{ $tahun }}"
                                                {{ $tahun == $tahunRiwayat ? 'selected' : '' }}>
                                                {{ $tahun }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                            <div class="p-5">
                                <div class="h-72">
                                    <canvas id="chartRiwayat"></canvas>
                                </div>
                                <div class="text-center text-xs text-slate-500 mt-5">
                                    <span class="font-semibold text-slate-700">
                                        {{ $chartRiwayat['pegawai_riwayat'] }}
                                    </span>
                                    Pegawai
                                    <span class="mx-2">•</span>
                                    <span class="font-semibold text-slate-700">
                                        {{ $chartRiwayat['total_kgb'] }}
                                    </span>
                                    KGB
                                    <span class="mx-2">•</span>
                                    <span class="font-semibold text-slate-700">
                                        {{ $chartRiwayat['total_pangkat'] }}
                                    </span>
                                    Pangkat
                                    <span class="mx-2">•</span>
                                    <span class="font-semibold text-slate-700">
                                        {{ $chartRiwayat['total_pensiun'] }}
                                    </span>
                                    Pensiun
                                </div>
                                <div id="legendRiwayat" class="mt-6 space-y-2">
                                </div>
                            </div>
                        </div> --}}
                    {{-- </div> --}}
                </div>
            </div>
        </main>
    </div>
    <script>
        window.chartJenisPegawai = @json($chartJenisPegawai);
        window.ChartJenisKelamin = @json($ChartJenisKelamin);
        window.chartReminder = @json($chartReminder);
        window.chartPangkat = @json($chartPangkat);
        window.chartPendidikan = @json($chartPendidikan);
        window.chartStatus = @json($chartStatus);
        window.chartUsia = @json($chartUsia);
        window.chartMasaKerja = @json($chartMasaKerja);
        window.chartRiwayat = @json($chartRiwayat);
         window.chartGolonganPppk = @json($chartGolonganPppk);
    </script>
</body>

</html>
