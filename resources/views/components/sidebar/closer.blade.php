<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="{{ route('deals.index') }}">
                <img class="rounded-2" src="{{ asset('images/logos/logo.webp') }}" alt="{{ config('app.name') }}" style="max-height: 48px;">
            </a>
        </h1>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                <!-- My Deals -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('deals.index') ? 'active' : '' }}" href="{{ route('deals.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17l6 -6l4 4l8 -8" /><path d="M14 7l7 0l0 7" /></svg>
                        </span>
                        <span class="nav-link-title">{{ auth()->user()->can('deals.view-all') ? 'All Deals' : 'My Deals' }}</span>
                    </a>
                </li>

                <!-- New Deal -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('deals.create') ? 'active' : '' }}" href="{{ route('deals.create') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                        </span>
                        <span class="nav-link-title">New Deal</span>
                    </a>
                </li>

                <!-- Customers (admin-granted access only) -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('closer.companies.*', 'closer.agents.*') ? 'active' : '' }}" href="{{ route('closer.companies.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M9 8l1 0" /><path d="M9 12l1 0" /><path d="M9 16l1 0" /><path d="M14 8l1 0" /><path d="M14 12l1 0" /><path d="M14 16l1 0" /><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" /></svg>
                        </span>
                        <span class="nav-link-title">Customers</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>
