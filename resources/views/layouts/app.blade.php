<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite('resources/css/app.css')
</head>

<body class="overflow-x-hidden" data-validation-errors="{{ $errors->any() ? 'true' : 'false' }}">
    <div class="flex">
        {{-- SIDEBAR --}}
        @include('partials.sidebar')

        <main id="mainContent" class="w-full min-w-0 flex-1 ml-0 lg:ml-72 transition-all duration-300">
            {{-- SIDEBAR --}}
            @include('partials.topbar')
            {{-- CONTENT --}}
            @yield('content')
            @include('components.support')
            @include('auth.logout')
        </main>
    </div>
    <audio id="notificationSound" preload="auto">
        <source src="{{ asset('sounds/notif.mp3') }}" type="audio/mpeg">
    </audio>
    <script>
        window.notificationConfig = {
            unreadUrl: "{{ route('notification.unread-count') }}"
        };

        @isset($chartJenisPegawai)
            window.chartJenisPegawai = @json($chartJenisPegawai);
        @endisset

        @isset($ChartJenisKelamin)
            window.ChartJenisKelamin = @json($ChartJenisKelamin);
        @endisset

        @isset($chartReminder)
            window.chartReminder = @json($chartReminder);
        @endisset
        @isset($chartPangkat)
            window.chartPangkat = @json($chartPangkat);
        @endisset
    </script>
    @vite('resources/js/app.js')
</body>

</html>
