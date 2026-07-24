<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function () {
            var theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    <title>@yield('title', 'Login') - {{ config('app.name') }}</title>

    {{-- Favicons --}}
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logos/favicons/favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logos/favicons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('images/logos/favicons/favicon-48x48.png') }}">
    <link rel="icon" href="{{ asset('images/logos/favicons/favicon.ico') }}">

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/logos/favicons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/logos/favicons/android-chrome-192x192.png') }}">
    <link rel="manifest" href="{{ asset('images/logos/favicons/site.webmanifest') }}">
    <meta name="msapplication-TileColor" content="#f26422">
    <meta name="msapplication-TileImage" content="{{ asset('images/logos/favicons/android-chrome-192x192.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="d-flex flex-column bg-white">
    <div class="page page-center">
        @include('components.page-loader')

        <div class="container container-tight py-4">
            <!-- Logo -->
            <div class="text-center mb-4">
                <a href="/" class="navbar-brand navbar-brand-autodark">
                    <img src="{{ asset('images/logos/logo.webp') }}" alt="{{ config('app.name') }}" style="max-height: 48px;">
                </a>
            </div>

            <!-- Alerts -->
            @include('components.alerts.flash')

            <!-- Content -->
            @yield('content')

            <!-- Footer -->
            <div class="text-center text-muted mt-4">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Custom JS -->
    <script src="{{ asset('js/custom.js') }}"></script>

    @stack('scripts')
</body>
</html>
