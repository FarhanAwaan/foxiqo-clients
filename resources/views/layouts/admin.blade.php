<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }} Admin</title>

    <!-- Tabler CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler-vendors.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    @stack('styles')
</head>
<body class="layout-fluid">
    <div class="page">
        <!-- Sidebar -->
        @include('components.sidebar.admin')

        <div class="page-wrapper">
            <!-- Header -->
            @include('components.header.admin')

            <!-- Page Content -->
            <div class="page-body">
                <div class="container-xl">
                    <!-- Alerts -->
                    @include('components.alerts.flash')

                    <!-- Page Header -->
                    @hasSection('page-header')
                        <div class="page-header d-print-none mb-4">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    @hasSection('page-pretitle')
                                        <div class="page-pretitle">@yield('page-pretitle')</div>
                                    @endif
                                    <h2 class="page-title">@yield('page-header')</h2>
                                </div>
                                @hasSection('page-actions')
                                    <div class="col-auto ms-auto">
                                        @yield('page-actions')
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Main Content -->
                    @yield('content')
                </div>
            </div>

            <!-- Footer -->
            <footer class="footer footer-transparent d-print-none">
                <div class="container-xl">
                    <div class="row text-center align-items-center">
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">
                                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Tabler JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>

    <!-- Custom JS -->
    <script src="{{ asset('js/custom.js') }}"></script>

    @stack('scripts')
</body>
</html>
