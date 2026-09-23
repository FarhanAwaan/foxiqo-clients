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

                <!-- Nsave (manual bank transfer) -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M3 10l18 0" /><path d="M5 6l7 -3l7 3" /><path d="M4 10l0 11" /><path d="M20 10l0 11" /><path d="M8 14l0 3" /><path d="M12 14l0 3" /><path d="M16 14l0 3" /></svg>
                            Nsave (Bank Transfer)
                        </h3>
                        <div class="card-actions">
                            <label class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="nsave_enabled" value="1" {{ old('nsave_enabled', $settings['nsave_enabled']) ? 'checked' : '' }}>
                                <span class="form-check-label">Enabled</span>
                            </label>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0">
                            Manual bank-transfer rail: the customer wires funds to the Nsave account
                            below and uploads a receipt for review. Bank details come from <code>.env</code>
                            (<code>config/billing.php</code>) — turn this off here at any time to hide the
                            bank-transfer option from customers, e.g. if the account is temporarily unavailable.
                        </p>
                    </div>
                </div>

                <!-- Paddle Integration -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M3 10l18 0" /><path d="M7 15l.01 0" /><path d="M11 15l2 0" /></svg>
                            Paddle Integration
                        </h3>
                        <div class="card-actions d-flex align-items-center gap-3">
                            @if($hasValues['paddle_api_key'] && $hasValues['paddle_webhook_secret'])
                                <span class="badge bg-green-lt">Connected</span>
                            @else
                                <span class="badge bg-yellow-lt">Not Configured</span>
                            @endif
                            <label class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="paddle_enabled" value="1" {{ old('paddle_enabled', $settings['paddle_enabled']) ? 'checked' : '' }}>
                                <span class="form-check-label">Enabled</span>
                            </label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="paddle_environment">Environment</label>
                                <select name="paddle_environment" id="paddle_environment" class="form-select @error('paddle_environment') is-invalid @enderror">
                                    <option value="sandbox" {{ old('paddle_environment', $settings['paddle_environment']) == 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                    <option value="live" {{ old('paddle_environment', $settings['paddle_environment']) == 'live' ? 'selected' : '' }}>Live</option>
                                </select>
                                @error('paddle_environment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="paddle_product_id">Catalog Product ID</label>
                                <input type="text"
                                       name="paddle_product_id"
                                       id="paddle_product_id"
                                       class="form-control @error('paddle_product_id') is-invalid @enderror"
                                       value="{{ old('paddle_product_id', $settings['paddle_product_id']) }}"
                                       placeholder="pro_...">
                                @error('paddle_product_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Created once in the Paddle dashboard (e.g. "FOXIQO AI Receptionist")</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="paddle_api_key">API Key</label>
                                <div class="input-group">
                                    <input type="password"
                                           name="paddle_api_key"
                                           id="paddle_api_key"
                                           class="form-control @error('paddle_api_key') is-invalid @enderror"
                                           placeholder="{{ $hasValues['paddle_api_key'] ? '••••••••••••••••' : 'Enter API Key' }}"
                                           autocomplete="off">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="paddle_api_key">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                    </button>
                                </div>
                                @error('paddle_api_key')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Server-side only. Leave blank to keep existing key</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="paddle_webhook_secret">Webhook Secret</label>
                                <div class="input-group">
                                    <input type="password"
                                           name="paddle_webhook_secret"
                                           id="paddle_webhook_secret"
                                           class="form-control @error('paddle_webhook_secret') is-invalid @enderror"
                                           placeholder="{{ $hasValues['paddle_webhook_secret'] ? '••••••••••••••••' : 'Enter Webhook Secret' }}"
                                           autocomplete="off">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="paddle_webhook_secret">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                    </button>
                                </div>
                                @error('paddle_webhook_secret')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Used to verify Paddle webhook events</div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label" for="paddle_client_side_token">Client-side Token</label>
                                <div class="input-group">
                                    <input type="password"
                                           name="paddle_client_side_token"
                                           id="paddle_client_side_token"
                                           class="form-control @error('paddle_client_side_token') is-invalid @enderror"
                                           placeholder="{{ $hasValues['paddle_client_side_token'] ? '••••••••••••••••' : 'test_... or live_...' }}"
                                           autocomplete="off">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="paddle_client_side_token">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                    </button>
                                </div>
                                @error('paddle_client_side_token')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Encrypted at rest, like the fields above. Leave blank to keep existing value — this is still the token that gets sent to the browser on the checkout page itself (that part's unavoidable, it's how Paddle.js works), but it's stored encrypted and only shown here when you click the eye.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Google Calendar Integration -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /><path d="M11 15h1" /><path d="M12 15v3" /></svg>
                            Google Calendar Integration
                        </h3>
                        <div class="card-actions">
                            @if($hasValues['google_calendar_client_secret'])
                                <span class="badge bg-green-lt">Configured</span>
                            @else
                                <span class="badge bg-yellow-lt">Not Configured</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="google_calendar_client_id">OAuth Client ID</label>
                                <input type="text"
                                       name="google_calendar_client_id"
                                       id="google_calendar_client_id"
                                       class="form-control @error('google_calendar_client_id') is-invalid @enderror"
                                       value="{{ old('google_calendar_client_id', $settings['google_calendar_client_id']) }}"
                                       placeholder="xxxxxxxxxx.apps.googleusercontent.com">
                                @error('google_calendar_client_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="google_calendar_client_secret">OAuth Client Secret</label>
                                <div class="input-group">
                                    <input type="password"
                                           name="google_calendar_client_secret"
                                           id="google_calendar_client_secret"
                                           class="form-control @error('google_calendar_client_secret') is-invalid @enderror"
                                           placeholder="{{ $hasValues['google_calendar_client_secret'] ? '••••••••••••••••' : 'Enter OAuth Client Secret' }}"
                                           autocomplete="off">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="google_calendar_client_secret">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                    </button>
                                </div>
                                @error('google_calendar_client_secret')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Leave blank to keep existing secret</div>
                            </div>
                        </div>
                        <div class="form-hint mb-0">
                            One OAuth app for the whole agency. Create it in the Google Cloud Console with an authorized redirect URI of
                            <code>{{ url('/admin/calendar/google/callback') }}</code>, then agents connect their own calendar individually from their assistant page.
                        </div>
                    </div>
                </div>

                <!-- Billing Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 7l1 0" /><path d="M9 13l6 0" /><path d="M13 17l2 0" /></svg>
                            Billing
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
                                <label class="form-label required" for="usage_alert_minutes_threshold">Usage Alert Threshold</label>
                                <div class="input-group">
                                    <input type="number"
                                           name="usage_alert_minutes_threshold"
                                           id="usage_alert_minutes_threshold"
                                           class="form-control @error('usage_alert_minutes_threshold') is-invalid @enderror"
                                           value="{{ old('usage_alert_minutes_threshold', $settings['usage_alert_minutes_threshold']) }}"
                                           min="1"
                                           required>
                                    <span class="input-group-text">min</span>
                                </div>
                                @error('usage_alert_minutes_threshold')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">
                                    Minutes used in a period that trigger an internal-only email to you —
                                    nothing is paused or shown to the customer.
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <label class="form-check form-switch mb-0">
                                    <input type="checkbox" name="usage_alert_enabled" value="1" class="form-check-input"
                                           {{ old('usage_alert_enabled', $settings['usage_alert_enabled']) ? 'checked' : '' }}>
                                    <span class="form-check-label">Enable usage alert emails</span>
                                </label>
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
                                    API keys and secrets are encrypted before being stored. Leave fields blank to keep existing values, or click a field's eye to view its stored value (each view is logged).
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
                        <a href="https://vendors.paddle.com" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center">
                            <span class="me-2">Paddle Dashboard</span>
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
                                <div class="datagrid-title">Nsave</div>
                                <div class="datagrid-content">
                                    @if($settings['nsave_enabled'])
                                        <span class="status status-green">
                                            <span class="status-dot status-dot-animated"></span>
                                            Enabled
                                        </span>
                                    @else
                                        <span class="status status-yellow">
                                            <span class="status-dot"></span>
                                            Disabled
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Paddle</div>
                                <div class="datagrid-content">
                                    @if($hasValues['paddle_api_key'] && $settings['paddle_enabled'])
                                        <span class="status status-green">
                                            <span class="status-dot status-dot-animated"></span>
                                            Enabled
                                        </span>
                                    @elseif($hasValues['paddle_api_key'])
                                        <span class="status status-yellow">
                                            <span class="status-dot"></span>
                                            Configured, Disabled
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

@push('styles')
<style>
    /* A stored secret shows its bullets at full strength, so it reads as "pre-filled but hidden" rather than an empty field. */
    .form-control.is-configured::placeholder { color: inherit; opacity: 1; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Secrets that have a stored value. Their real values aren't in this page — the eye
    // button fetches one from the server on click (admin-only, audit-logged).
    const stored = @json(array_keys(array_filter($hasValues)));
    const revealUrl = @json(url('admin/settings/reveal'));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const revealed = {}; // fetched values still on screen, so hiding can tell "untouched" from "edited"

    document.querySelectorAll('.toggle-password').forEach(function(button) {
        const targetId = button.getAttribute('data-target');

        if (stored.includes(targetId)) {
            document.getElementById(targetId).classList.add('is-configured');
            button.title = 'Show stored value';
        }

        // Toggle password visibility
        button.addEventListener('click', async function() {
            const input = document.getElementById(targetId);

            if (input.type === 'password') {
                if (stored.includes(targetId) && input.value === '') {
                    this.disabled = true;
                    try {
                        const response = await fetch(revealUrl + '/' + targetId, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        });
                        const data = await response.json();
                        if (!response.ok || typeof data.value !== 'string') throw new Error('unexpected response');
                        revealed[targetId] = data.value;
                        input.value = data.value;
                    } catch (e) {
                        alert('Could not load the stored value. Reload the page and try again — your session may have expired.');
                        return;
                    } finally {
                        this.disabled = false;
                    }
                }

                input.type = 'text';
                this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.585 10.587a2 2 0 0 0 2.829 2.828" /><path d="M16.681 16.673a8.717 8.717 0 0 1 -4.681 1.327c-3.6 0 -6.6 -2 -9 -6c1.272 -2.12 2.712 -3.678 4.32 -4.674m2.86 -1.146a9.055 9.055 0 0 1 1.82 -.18c3.6 0 6.6 2 9 6c-.666 1.11 -1.379 2.067 -2.138 2.87" /><path d="M3 3l18 18" /></svg>';
            } else {
                if (input.value === revealed[targetId]) input.value = ''; // untouched → back to blank, i.e. keep existing
                delete revealed[targetId];
                input.type = 'password';
                this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>';
            }
        });
    });
});
</script>
@endpush
