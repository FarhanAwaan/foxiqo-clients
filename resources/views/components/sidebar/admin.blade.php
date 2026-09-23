<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="{{ route('admin.dashboard') }}">
                <img class="rounded-2" src="{{ asset('images/logos/logo.webp') }}" alt="{{ config('app.name') }}" style="max-height: 48px;">
            </a>
        </h1>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>
                        </span>
                        <span class="nav-link-title">Dashboard</span>
                    </a>
                </li>

                <li class="nav-item pt-2">
                    <span class="nav-link disabled text-uppercase small">Management</span>
                </li>

                <!-- Customers (Company model/routes unchanged — display label only) -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}" href="{{ route('admin.companies.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M9 8l1 0" /><path d="M9 12l1 0" /><path d="M9 16l1 0" /><path d="M14 8l1 0" /><path d="M14 12l1 0" /><path d="M14 16l1 0" /><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" /></svg>
                        </span>
                        <span class="nav-link-title">Customers</span>
                        @php $unprovisionedCompanyCount = \App\Models\Company::where('status', 'active')->doesntHave('agents')->count(); @endphp
                        @if($unprovisionedCompanyCount > 0)
                            <span class="badge badge-sm bg-yellow-lt text-yellow ms-auto" title="Active customers with no assistant set up yet">{{ $unprovisionedCompanyCount }}</span>
                        @endif
                    </a>
                </li>

                <!-- Users -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
                        </span>
                        <span class="nav-link-title">Users</span>
                    </a>
                </li>

                <!-- Assistants -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.agents.*') ? 'active' : '' }}" href="{{ route('admin.agents.index') }}">
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
                        <span class="nav-link-title">Assistants</span>
                    </a>
                </li>

                <li class="nav-item pt-2">
                    <span class="nav-link disabled text-uppercase small">Billing</span>
                </li>

                <!-- Deals -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('deals.*') ? 'active' : '' }}" href="{{ route('deals.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17l6 -6l4 4l8 -8" /><path d="M14 7l7 0l0 7" /></svg>
                        </span>
                        <span class="nav-link-title">Deals</span>
                    </a>
                </li>

                @php
                    $billingRoutes = ['admin.billing.*', 'admin.subscriptions.*', 'admin.invoices.*', 'admin.receipts.*', 'admin.plans.*'];
                    $inBillingSection = request()->routeIs($billingRoutes);
                @endphp

                <!-- Billing & Usage -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ $inBillingSection ? 'active' : '' }}" href="#navbar-billing-usage" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="{{ $inBillingSection ? 'true' : 'false' }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v3a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M3 13m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z" /><path d="M14 13l7 0" /><path d="M14 17l7 0" /><path d="M14 21l7 0" /></svg>
                        </span>
                        <span class="nav-link-title">Billing &amp; Usage</span>
                        @php $overdueCompanyCount = \App\Models\Invoice::overdue()->distinct('company_id')->count('company_id'); @endphp
                        @if($overdueCompanyCount > 0)
                            <span class="badge badge-sm bg-red-lt text-red ms-auto">{{ $overdueCompanyCount }}</span>
                        @endif
                    </a>
                    <div class="dropdown-menu {{ $inBillingSection ? 'show' : '' }}">
                        <a class="dropdown-item {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}" href="{{ route('admin.billing.index') }}">Overview</a>
                        <a class="dropdown-item {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}" href="{{ route('admin.subscriptions.index') }}">Subscriptions</a>
                        <a class="dropdown-item {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}">Invoices</a>
                        <a class="dropdown-item {{ request()->routeIs('admin.receipts.*') ? 'active' : '' }}" href="{{ route('admin.receipts.index') }}">
                            Transfer Receipts
                            @php $pendingReceiptCount = \App\Models\PaymentReceipt::pending()->count(); @endphp
                            @if($pendingReceiptCount > 0)
                                <span class="badge badge-sm bg-red-lt text-red ms-1">{{ $pendingReceiptCount }}</span>
                            @endif
                        </a>
                        <a class="dropdown-item {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}" href="{{ route('admin.plans.index') }}">Plans</a>
                    </div>
                </li>

                <li class="nav-item pt-2">
                    <span class="nav-link disabled text-uppercase small">System</span>
                </li>

                <!-- Settings -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg>
                        </span>
                        <span class="nav-link-title">Settings</span>
                    </a>
                </li>

                <!-- Activity Logs -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 8l0 4l2 2" /><path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5" /></svg>
                        </span>
                        <span class="nav-link-title">Activity Logs</span>
                    </a>
                </li>

                <!-- Emails -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}" href="{{ route('admin.notifications.index') }}">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg>
                        </span>
                        <span class="nav-link-title">Emails</span>
                        @php $failedEmailCount = \App\Models\Notification::where('channel','email')->where('status','failed')->count(); @endphp
                        @if($failedEmailCount > 0)
                            <span class="badge badge-sm bg-red-lt text-red ms-auto">{{ $failedEmailCount }}</span>
                        @endif
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>
