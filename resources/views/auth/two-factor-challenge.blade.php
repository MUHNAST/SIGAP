<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi 2FA - SIGAP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-100 via-white to-slate-100 flex items-center justify-center p-6">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 sm:p-10">
        {{-- Ikon keamanan --}}
        <div class="w-16 h-16 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center mx-auto mb-6">
            <i class="fa-solid fa-shield-halved text-3xl"></i>
        </div>
        {{-- Judul --}}
        <h1 class="text-2xl font-bold text-slate-800 text-center">
            Verifikasi Dua Faktor
        </h1>
        <p class="text-sm text-slate-500 text-center mt-3 mb-8">
            Masukkan kode OTP 6 digit yang dikirim ke email akun Anda.
            Kode berlaku selama 2 menit.
        </p>
        {{-- Pesan berhasil --}}
        @if (session('status'))
            <div class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif
        {{-- Pesan kesalahan --}}
        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-600">
                {{ $errors->first('code') ?? $errors->first() }}
            </div>
        @endif
        {{-- Form verifikasi OTP --}}
        <form method="POST" action="{{ route('auth.two-factor.verify') }}" class="space-y-5">
            @csrf
            <div>
                <label for="code" class="block text-sm font-medium text-slate-700 mb-2">
                    Kode OTP dari Email
                </label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                    pattern="[0-9]{6}" maxlength="6" autofocus required placeholder="Masukkan 6 digit kode"
                    value="{{ old('code') }}"
                    class="w-full h-14 rounded-xl border border-slate-300 px-4 text-center text-lg tracking-widest outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
            </div>
            <button type="submit"
                class="w-full h-14 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold transition">
                Verifikasi dan Login
            </button>
        </form>
        {{-- Kirim ulang OTP --}}
        <div class="mt-6 text-center">
            <p class="text-sm text-slate-500 mb-3">
                Belum menerima email?
            </p>
            <form method="POST" action="{{ route('auth.two-factor.resend') }}">
                @csrf
                <button id="resendButton" type="submit" disabled
                    class="inline-flex items-center justify-center gap-2 text-sm font-semibold text-slate-400 cursor-not-allowed disabled:opacity-100">
                    <i id="resendIcon" class="fa-solid fa-circle-stop" aria-hidden="true"></i>
                    <span id="resendText">
                        Kirim ulang kode
                    </span>
                    <span id="resendCountdown" aria-live="off"></span>
                </button>
            </form>
        </div>
        {{-- Kembali ke login --}}
        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-800">
                Kembali ke halaman login
            </a>
        </div>
        {{-- Footer --}}
        <p class="mt-8 text-center text-xs text-slate-400">
            © 2026 SIGAP
        </p>
    </div>
    {{-- Hitung mundur kirim ulang OTP --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const button = document.getElementById('resendButton');
            const icon = document.getElementById('resendIcon');
            const text = document.getElementById('resendText');
            const countdown = document.getElementById('resendCountdown');
            let remaining = Math.max(
                0,
                Number(@json($resendWait ?? 60))
            );
            function updateCountdown() {
                if (remaining > 0) {
                    // Tombol tetap nonaktif selama cooldown.
                    button.disabled = true;
                    button.classList.remove(
                        'text-blue-600',
                        'hover:text-blue-800',
                        'cursor-pointer'
                    );
                    button.classList.add(
                        'text-slate-400',
                        'cursor-not-allowed'
                    );
                    // Ikon lingkaran berhenti.
                    icon.className = 'fa-solid fa-circle-stop';
                    text.textContent = 'Kirim ulang kode';
                    countdown.textContent = `(${remaining} detik)`;
                    remaining--;
                    setTimeout(updateCountdown, 1000);
                    return;
                }
                // Cooldown selesai: tombol dapat digunakan.
                button.disabled = false;
                button.classList.remove(
                    'text-slate-400',
                    'cursor-not-allowed'
                );
                button.classList.add(
                    'text-blue-600',
                    'hover:text-blue-800',
                    'cursor-pointer'
                );
                // Ganti ikon menjadi ikon kirim ulang.
                icon.className = 'fa-solid fa-rotate-right';
                text.textContent = 'Kirim ulang kode';
                countdown.textContent = '';
            }
            updateCountdown();
        });
    </script>
</body>
</html>
