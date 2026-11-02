<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIGAP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-blue-100 via-white to-slate-100 flex items-center justify-center p-6">
    <div class="w-full max-w-6xl bg-white rounded-[35px] overflow-hidden shadow-2xl grid lg:grid-cols-2">
        <!-- LEFT -->
        <div
            class="hidden lg:flex bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 text-white p-14 flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center">
                        <i class="fa-solid fa-building-columns text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-extrabold">
                            SIGAP
                        </h1>
                        <p class="text-blue-100">
                            Sistem Informasi Gaji, Pangkat, dan Pensiun
                        </p>
                    </div>
                </div>
                <h2 class="text-5xl font-bold leading-tight">
                    Pantau Jadwal Penting Pegawai
                </h2>
                <p class="mt-6 text-blue-100 leading-relaxed">
                    SIGAP membantu instansi memantau jadwal kenaikan pangkat,
                    kenaikan gaji berkala (KGB), dan masa pensiun pegawai melalui
                    dashboard monitoring serta notifikasi otomatis sehingga setiap
                    proses administrasi dapat dipersiapkan secara tepat waktu.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-5 mt-4">
                <div class="bg-white/10 rounded-2xl p-5 backdrop-blur">
                    <i class="fa-solid fa-bell text-2xl mb-3"></i>
                    <h3 class="font-semibold">
                        Pengingat Otomatis
                    </h3>
                    <p class="text-sm text-blue-100 mt-1">
                        Notifikasi KGB, kenaikan pangkat, dan pensiun.
                    </p>
                </div>
                <div class="bg-white/10 rounded-2xl p-5 backdrop-blur">
                    <i class="fa-solid fa-chart-line text-2xl mb-3"></i>
                    <h3 class="font-semibold">
                        Dashboard Monitoring
                    </h3>
                    <p class="text-sm text-blue-100 mt-1">
                        Pantau seluruh jadwal penting pegawai secara real-time.
                    </p>
                </div>
            </div>
        </div>
        <!-- RIGHT -->
        <div class="p-8 lg:p-14 flex items-center">
            <div class="w-full">
                <div class="lg:hidden text-center mb-10">
                    <div
                        class="w-20 h-20 rounded-3xl bg-blue-600 text-white mx-auto flex items-center justify-center mb-4 shadow-lg">
                        <i class="fa-solid fa-calendar-check text-3xl"></i>
                    </div>
                    <h1 class="text-3xl font-extrabold">
                        SIGAP
                    </h1>
                    <p class="text-slate-500 mt-2">
                        Sistem Informasi Gaji, Pangkat, dan Pensiun
                    </p>
                </div>
                <h2 class="text-3xl font-bold text-slate-800">
                    Login
                </h2>
                <p class="text-slate-500 mt-2 mb-8">
                    Masuk untuk memantau jadwal kenaikan pangkat, KGB, dan pensiun pegawai.
                </p>
                <form method="POST" action="{{ route('login.process') }}" class="space-y-6">
                    @csrf
                    <!-- Username -->
                    <div>
                        <label class="block mb-2 text-sm font-medium">
                            Username
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input autofocus type="text" name="username" placeholder="Masukkan Username"
                                autocomplete="username" autofocus
                                class="w-full h-14 rounded-2xl border
                                @error('username') border-red-500 focus:ring-red-500 @else border-slate-300 focus:ring-blue-500 @enderror
                                pl-12 pr-4 outline-none"
                                value="{{ old('username') }}" required>
                        </div>
                        @error('username')
                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <!-- Password -->
                    <div>
                        <label for="login_password" class="block mb-2 text-sm font-medium">
                            Password
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input id="login_password" name="password" type="password" placeholder="Masukkan Password"
                                autocomplete="current-password"
                                class="w-full h-14 rounded-2xl border @error('password') border-red-500 focus:ring-red-500
                                @else border-slate-300 focus:ring-blue-500 @enderror  pl-12 pr-12 outline-none"
                                required>
                            <button type="button" onclick="togglePassword('login_password', this)"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    {{-- <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember"
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                {{ old('remember') ? 'checked' : '' }}>
                            <span class="text-sm text-slate-600">
                                Ingat Saya
                            </span>
                        </label>
                    </div> --}}
                    <button type="submit"
                        class="flex items-center justify-center w-full h-14 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-semibold transition">
                        Login
                    </button>
                </form>
                <div class="mt-8 text-center text-sm text-slate-500">
                    © 2026 SIGAP
                    <br>
                    Sistem Informasi Gaji, Pangkat, dan Pensiun
                </div>
            </div>
        </div>
    </div>
</body>

</html>
