<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="{{ route('customer.dashboard') }}">
                <img class="rounded-2" src="{{ asset('images/logos/logo.webp') }}" alt="{{ config('app.name') }}" style="max-height: 48px;">
            </a>
        </h1>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}" href="{{ route('customer.dashboard') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>
                        </span>
                        <span class="nav-link-title">Dashboard</span>
                    </a>
                </li>

                <!-- Assistants -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('customer.agents.*') ? 'active' : '' }}" href="{{ route('customer.agents.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M6 6a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2l0 -4"></path>
                                <path d="M12 2v2"></path>
                                <path d="M9 12v9"></path>
                                <path d="M15 12v9"></path>
                                <path d="M5 16l4 -2"></path>
                                <path d="M15 14l4 2"></path>
                                <path d="M9 18h6"></path>
                                <path d="M10 8v.01"></path>
                                <path d="M14 8v.01"></path>
                            </svg>
                        </span>
                        <span class="nav-link-title">My Assistants</span>
                    </a>
                </li>

                <!-- Billing Section -->
                <li class="nav-item pt-2">
                    <span class="nav-link disabled text-uppercase small">Billing</span>
                </li>

                <!-- Subscriptions -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('customer.subscriptions.*') ? 'active' : '' }}" href="{{ route('customer.subscriptions.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17l6 -6l4 4l8 -8" /><path d="M14 7l7 0l0 7" /></svg>
                        </span>
                        <span class="nav-link-title">Subscriptions</span>
                    </a>
                </li>

                <!-- Invoices -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('customer.invoices.*') ? 'active' : '' }}" href="{{ route('customer.invoices.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 7l1 0" /><path d="M9 13l6 0" /><path d="M13 17l2 0" /></svg>
                        </span>
                        <span class="nav-link-title">Invoices</span>
                    </a>
                </li>
            </ul>

            <!-- Company Info -->
            @if(auth()->user()->company)
                <div class="mt-auto p-3">
                    <div class="small text-muted mb-1">Company</div>
                    <div class="text-white">{{ auth()->user()->company->name }}</div>
                </div>
            @endif
        </div>
    </div>
</aside>
