@extends('layouts.admin')

@section('title', 'System Settings')

@section('page-pretitle')
    Administration
@endsection

@section('page-header')
    System Settings
@endsection

@section('content')
    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf

        @if(session('success'))
            <div class="alert alert-success alert-dismissible mb-4" role="alert">
                <div class="d-flex">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                    </div>
                    <div>{{ session('success') }}</div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <!-- Company/Branding Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M9 8l1 0" /><path d="M9 12l1 0" /><path d="M9 16l1 0" /><path d="M14 8l1 0" /><path d="M14 12l1 0" /><path d="M14 16l1 0" /><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" /></svg>
                            Company & Branding
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required" for="company_name">Company Name</label>
                                <input type="text"
                                       name="company_name"
                                       id="company_name"
                                       class="form-control @error('company_name') is-invalid @enderror"
                                       value="{{ old('company_name', $settings['company_name']) }}"
                                       placeholder="Your Company Name"
                                       required>
                                @error('company_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">This name will appear in emails and invoices</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required" for="company_email">Support Email</label>
                                <input type="email"
                                       name="company_email"
                                       id="company_email"
                                       class="form-control @error('company_email') is-invalid @enderror"
                                       value="{{ old('company_email', $settings['company_email']) }}"
                                       placeholder="support@yourcompany.com"
                                       required>
                                @error('company_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Primary contact email for customer support</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Retell AI Integration -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855" /></svg>
                            Retell AI Integration
                        </h3>
                        <div class="card-actions">
                            @if($hasValues['retell_api_key'])
                                <span class="badge bg-green-lt">Connected</span>
                            @else
                                <span class="badge bg-yellow-lt">Not Configured</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="retell_api_key">API Key</label>
                                <div class="input-group">
                                    <input type="password"
                                           name="retell_api_key"
                                           id="retell_api_key"
                                           class="form-control @error('retell_api_key') is-invalid @enderror"
                                           placeholder="{{ $hasValues['retell_api_key'] ? '••••••••••••••••' : 'Enter API Key' }}"
                                           autocomplete="off">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="retell_api_key">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                    </button>
                                </div>
                                @error('retell_api_key')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Leave blank to keep existing key</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="retell_webhook_secret">Webhook Secret</label>
                                <div class="input-group">
                                    <input type="password"
                                           name="retell_webhook_secret"
                                           id="retell_webhook_secret"
                                           class="form-control @error('retell_webhook_secret') is-invalid @enderror"
                                           placeholder="{{ $hasValues['retell_webhook_secret'] ? '••••••••••••••••' : 'Enter Webhook Secret' }}"
                                           autocomplete="off">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="retell_webhook_secret">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                    </button>
                                </div>
                                @error('retell_webhook_secret')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Used to verify webhook requests</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stripe Integration -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M3 10l18 0" /><path d="M7 15l.01 0" /><path d="M11 15l2 0" /></svg>
                            Stripe Integration
                        </h3>
                        <div class="card-actions">
                            @if($hasValues['stripe_api_key'])
                                <span class="badge bg-green-lt">Connected</span>
                            @else
                                <span class="badge bg-yellow-lt">Not Configured</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="stripe_api_key">Secret API Key</label>
                                <div class="input-group">
                                    <input type="password"
                                           name="stripe_api_key"
                                           id="stripe_api_key"
                                           class="form-control @error('stripe_api_key') is-invalid @enderror"
                                           placeholder="{{ $hasValues['stripe_api_key'] ? '••••••••••••••••' : 'sk_live_...' }}"
                                           autocomplete="off">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="stripe_api_key">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                    </button>
                                </div>
                                @error('stripe_api_key')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Leave blank to keep existing key</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="stripe_webhook_secret">Webhook Signing Secret</label>
                                <div class="input-group">
                                    <input type="password"
                                           name="stripe_webhook_secret"
                                           id="stripe_webhook_secret"
                                           class="form-control @error('stripe_webhook_secret') is-invalid @enderror"
                                           placeholder="{{ $hasValues['stripe_webhook_secret'] ? '••••••••••••••••' : 'whsec_...' }}"
                                           autocomplete="off">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="stripe_webhook_secret">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                    </button>
                                </div>
                                @error('stripe_webhook_secret')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Used to verify Stripe webhook events</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payoneer Integration -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12" /><path d="M20 12v4h-4a2 2 0 0 1 0 -4h4" /></svg>
                            Payoneer Integration
                        </h3>
                        <div class="card-actions">
                            @if($hasValues['payoneer_api_key'])
                                <span class="badge bg-green-lt">Connected</span>
                            @else
                                <span class="badge bg-yellow-lt">Not Configured</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="payoneer_api_key">API Key</label>
                                <div class="input-group">
                                    <input type="password"
                                           name="payoneer_api_key"
                                           id="payoneer_api_key"
                                           class="form-control @error('payoneer_api_key') is-invalid @enderror"
                                           placeholder="{{ $hasValues['payoneer_api_key'] ? '••••••••••••••••' : 'Enter Payoneer API Key' }}"
                                           autocomplete="off">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="payoneer_api_key">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                    </button>
                                </div>
                                @error('payoneer_api_key')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Leave blank to keep existing key</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="payoneer_partner_id">Partner ID</label>
                                <input type="text"
                                       name="payoneer_partner_id"
                                       id="payoneer_partner_id"
                                       class="form-control @error('payoneer_partner_id') is-invalid @enderror"
                                       value="{{ old('payoneer_partner_id', $settings['payoneer_partner_id']) }}"
                                       placeholder="Your Payoneer Partner ID">
                                @error('payoneer_partner_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Your unique Payoneer partner identifier</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Billing Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 7l1 0" /><path d="M9 13l6 0" /><path d="M13 17l2 0" /></svg>
                            Billing & Invoicing
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label required" for="invoice_due_days">Invoice Due Days</label>
                                <div class="input-group">
                                    <input type="number"
                                           name="invoice_due_days"
                                           id="invoice_due_days"
                                           class="form-control @error('invoice_due_days') is-invalid @enderror"
                                           value="{{ old('invoice_due_days', $settings['invoice_due_days']) }}"
                                           min="1"
                                           max="30"
                                           required>
                                    <span class="input-group-text">days</span>
                                </div>
                                @error('invoice_due_days')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Days until invoice is due after creation</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label required" for="payment_link_expiry_days">Payment Link Expiry</label>
                                <div class="input-group">
                                    <input type="number"
                                           name="payment_link_expiry_days"
                                           id="payment_link_expiry_days"
                                           class="form-control @error('payment_link_expiry_days') is-invalid @enderror"
                                           value="{{ old('payment_link_expiry_days', $settings['payment_link_expiry_days']) }}"
                                           min="1"
                                           max="60"
                                           required>
                                    <span class="input-group-text">days</span>
                                </div>
                                @error('payment_link_expiry_days')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Days until payment links expire</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label required" for="circuit_breaker_threshold">Circuit Breaker Threshold</label>
                                <div class="input-group">
                                    <input type="number"
                                           name="circuit_breaker_threshold"
                                           id="circuit_breaker_threshold"
                                           class="form-control @error('circuit_breaker_threshold') is-invalid @enderror"
                                           value="{{ old('circuit_breaker_threshold', $settings['circuit_breaker_threshold']) }}"
                                           min="100"
                                           max="300"
                                           required>
                                    <span class="input-group-text">%</span>
                                </div>
                                @error('circuit_breaker_threshold')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Usage percentage that triggers circuit breaker</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Save Button Card -->
                <div class="card sticky-top" style="top: 1rem;">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>
                            Save All Settings
                        </button>
                    </div>
                </div>

                <!-- API Keys Info -->
                <div class="card bg-primary-lt">
                    <div class="card-body">
                        <div class="d-flex">
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-primary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>
                            </div>
                            <div class="ms-3">
                                <h4 class="mb-1">Sensitive Data</h4>
                                <p class="text-muted mb-0 small">
                                    API keys and secrets are encrypted before being stored. Leave fields blank to keep existing values.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Links</h3>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="https://dashboard.retellai.com" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="me-2">Retell AI Dashboard</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-auto" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" /><path d="M11 13l9 -9" /><path d="M15 4h5v5" /></svg>
                        </a>
                        <a href="https://dashboard.stripe.com" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="me-2">Stripe Dashboard</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-auto" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" /><path d="M11 13l9 -9" /><path d="M15 4h5v5" /></svg>
                        </a>
                        <a href="https://payoneer.com/partners" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="me-2">Payoneer Partners</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-auto" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" /><path d="M11 13l9 -9" /><path d="M15 4h5v5" /></svg>
                        </a>
                    </div>
                </div>

                <!-- Integration Status -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Integration Status</h3>
                    </div>
                    <div class="card-body">
                        <div class="datagrid">
                            <div class="datagrid-item">
                                <div class="datagrid-title">Retell AI</div>
                                <div class="datagrid-content">
                                    @if($hasValues['retell_api_key'])
                                        <span class="status status-green">
                                            <span class="status-dot status-dot-animated"></span>
                                            Connected
                                        </span>
                                    @else
                                        <span class="status status-yellow">
                                            <span class="status-dot"></span>
                                            Not Configured
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Stripe</div>
                                <div class="datagrid-content">
                                    @if($hasValues['stripe_api_key'])
                                        <span class="status status-green">
                                            <span class="status-dot status-dot-animated"></span>
                                            Connected
                                        </span>
                                    @else
                                        <span class="status status-yellow">
                                            <span class="status-dot"></span>
                                            Not Configured
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Payoneer</div>
                                <div class="datagrid-content">
                                    @if($hasValues['payoneer_api_key'])
                                        <span class="status status-green">
                                            <span class="status-dot status-dot-animated"></span>
                                            Connected
                                        </span>
                                    @else
                                        <span class="status status-yellow">
                                            <span class="status-dot"></span>
                                            Not Configured
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(function(button) {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);

            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" /></svg>';
            } else {
                input.type = 'password';
                this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>';
            }
        });
    });
});
</script>
@endpush
