@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.closer')

@section('title', 'New Deal')

@section('page-header')
    New Deal
@endsection

@section('content')
    @if(!$paddleAvailable)
        <div class="alert alert-warning mb-4">
            <div class="d-flex">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" /></svg>
                </div>
                <div>
                    <strong>Paddle is currently disabled or not fully configured.</strong>
                    Deals can't be created until an admin re-enables it in Settings.
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('deals.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Business Info</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required" for="customer_name">Contact Name</label>
                                <input type="text" name="customer_name" id="customer_name" class="form-control @error('customer_name') is-invalid @enderror" value="{{ old('customer_name') }}" required>
                                @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required" for="business_name">Business Name</label>
                                <input type="text" name="business_name" id="business_name" class="form-control @error('business_name') is-invalid @enderror" value="{{ old('business_name') }}" required>
                                @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required" for="email">Email</label>
                                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="phone">Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="industry">Industry</label>
                                <input type="text" name="industry" id="industry" class="form-control @error('industry') is-invalid @enderror" value="{{ old('industry') }}">
                                @error('industry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="city">City</label>
                                <input type="text" name="city" id="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}">
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="country">Country</label>
                                <input type="text" name="country" id="country" class="form-control @error('country') is-invalid @enderror" value="{{ old('country') }}">
                                @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="website">Website</label>
                                <input type="text" name="website" id="website" class="form-control @error('website') is-invalid @enderror" value="{{ old('website') }}">
                                @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Agreed Pricing</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required" for="agreed_monthly_price">Monthly Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" name="agreed_monthly_price" id="agreed_monthly_price" class="form-control @error('agreed_monthly_price') is-invalid @enderror" value="{{ old('agreed_monthly_price') }}" required>
                                </div>
                                @error('agreed_monthly_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                @if(!$canOverrideFloor)
                                    <div class="form-hint">Minimum $497/mo</div>
                                @endif
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required" for="activation_price">Activation Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" name="activation_price" id="activation_price" class="form-control @error('activation_price') is-invalid @enderror" value="{{ old('activation_price', 999) }}" required>
                                </div>
                                @error('activation_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                @if(!$canOverrideFloor)
                                    <div class="form-hint">Minimum $999 one-time</div>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" name="is_trial" value="1" class="form-check-input" id="isTrialToggle" {{ old('is_trial') ? 'checked' : '' }}>
                                <span class="form-check-label">Start with a free trial</span>
                            </label>
                            <small class="text-muted d-block">
                                The activation fee still charges today. The monthly price is what
                                Paddle charges after the trial ends — the customer pays nothing
                                recurring until then.
                            </small>
                        </div>
                        <div id="trialDaysWrapper" class="mb-3" style="{{ old('is_trial') ? '' : 'display:none;' }}">
                            <label class="form-label" for="trial_days">Trial Length (days)</label>
                            <input type="number" name="trial_days" id="trial_days" min="1" max="365"
                                   class="form-control @error('trial_days') is-invalid @enderror"
                                   value="{{ old('trial_days', 30) }}">
                            @error('trial_days')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100" {{ $paddleAvailable ? '' : 'disabled' }}>
                            Create Deal &amp; Get Checkout Link
                        </button>
                        <p class="text-muted small mt-3 mb-0">
                            This creates the customer in Paddle and generates a checkout link
                            you can send right away — nothing in the portal is created until
                            they actually pay.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.getElementById('isTrialToggle')?.addEventListener('change', function () {
            document.getElementById('trialDaysWrapper').style.display = this.checked ? '' : 'none';
        });
    </script>
@endsection
