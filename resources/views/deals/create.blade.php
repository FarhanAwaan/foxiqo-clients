@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.closer')

@section('title', $parent ? 'Add Monthly Retainer' : 'New Deal')

@section('page-header')
    {{ $parent ? 'Add Monthly Retainer' : 'New Deal' }}
@endsection

@section('content')
    @php
        $mode = old('billing_mode', 'recurring');
        $isRecurring = $parent || $mode === 'recurring';
    @endphp

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
        @if($parent)
            <input type="hidden" name="parent_deal" value="{{ $parent->uuid }}">
        @endif

        <div class="row">
            <div class="col-lg-8">
                @if($parent)
                    {{-- Retainer deal: the customer comes from the setup deal, only pricing is asked for --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Customer</h3>
                        </div>
                        <div class="card-body">
                            <div class="datagrid">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Business</div>
                                    <div class="datagrid-content">{{ $parent->business_name }}</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Contact</div>
                                    <div class="datagrid-content">{{ $parent->customer_name }}</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Email</div>
                                    <div class="datagrid-content">{{ $parent->email }}</div>
                                </div>
                            </div>
                            <p class="text-muted small mt-3 mb-0">
                                Same customer as the paid setup deal — the retainer attaches to their existing account.
                                No setup fee is charged again.
                            </p>
                        </div>
                    </div>
                @else
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
                                    <div class="form-hint">The checkout link, receipts and portal login invitation go here.</div>
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
                            <h3 class="card-title">What are they agreeing to?</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="billing_mode" value="recurring" class="form-selectgroup-input" {{ $mode === 'recurring' ? 'checked' : '' }}>
                                    <div class="form-selectgroup-label d-flex align-items-center p-3">
                                        <div class="me-3"><span class="form-selectgroup-check"></span></div>
                                        <div>
                                            <strong>Setup fee + monthly retainer</strong>
                                            <div class="text-muted small">The monthly price is agreed now. Optionally start it after a free trial — including "when the assistant is live".</div>
                                        </div>
                                    </div>
                                </label>
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="billing_mode" value="setup_only" class="form-selectgroup-input" {{ $mode === 'setup_only' ? 'checked' : '' }}>
                                    <div class="form-selectgroup-label d-flex align-items-center p-3">
                                        <div class="me-3"><span class="form-selectgroup-check"></span></div>
                                        <div>
                                            <strong>Setup fee only — monthly price agreed later</strong>
                                            <div class="text-muted small">Only the setup fee is charged and nothing recurring is created. Once the monthly price is agreed, add it from this deal (it gets its own checkout link).</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Agreed Pricing</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3" id="monthlyWrapper" style="{{ $isRecurring ? '' : 'display:none;' }}">
                                <label class="form-label required" for="agreed_monthly_price">Monthly Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0.01" name="agreed_monthly_price" id="agreed_monthly_price" class="form-control @error('agreed_monthly_price') is-invalid @enderror" value="{{ old('agreed_monthly_price') }}" {{ $isRecurring ? 'required' : '' }}>
                                </div>
                                @error('agreed_monthly_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-hint">
                                    What Paddle charges every month — never $0.
                                    @if(!$canOverrideFloor) Minimum $497/mo. @endif
                                </div>
                            </div>
                            @unless($parent)
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required" for="activation_price">Setup &amp; Activation Fee</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" min="0" name="activation_price" id="activation_price" class="form-control @error('activation_price') is-invalid @enderror" value="{{ old('activation_price', 999) }}" required>
                                    </div>
                                    @error('activation_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    <div class="form-hint">
                                        One-time, charged at checkout.
                                        @if(!$canOverrideFloor) Minimum $999. @else <span id="waiveHint">Set $0 to waive it (retainer-only deal).</span> @endif
                                    </div>
                                </div>
                            @endunless
                        </div>

                        <div id="trialWrapper" style="{{ $isRecurring ? '' : 'display:none;' }}">
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="is_trial" value="1" class="form-check-input" id="isTrialToggle" {{ old('is_trial') ? 'checked' : '' }}>
                                    <span class="form-check-label">Delay the first monthly charge (free trial)</span>
                                </label>
                                <small class="text-muted d-block">
                                    @unless($parent) The setup fee still charges today. @endunless
                                    The card is entered at checkout and charged automatically when the trial ends — nothing recurring is due until then.
                                    You can move that date or start billing early from the deal page, e.g. to hold the first charge until the assistant is live.
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
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <input type="hidden" name="email_link" value="0">
                        <label class="form-check mb-3">
                            <input type="checkbox" name="email_link" value="1" class="form-check-input" {{ old('email_link', 1) ? 'checked' : '' }}>
                            <span class="form-check-label">Email the checkout link to the customer now</span>
                            <small class="text-muted d-block">They get an order summary and the secure link. The admin is notified either way; untick to send the link yourself.</small>
                        </label>

                        <button type="submit" class="btn btn-primary w-100" {{ $paddleAvailable ? '' : 'disabled' }}>
                            {{ $parent ? 'Create Retainer Deal' : 'Create Deal & Get Checkout Link' }}
                        </button>
                        <p class="text-muted small mt-3 mb-0">
                            This creates the customer in Paddle and generates a checkout link —
                            nothing in the portal is created until they actually pay.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        (function () {
            const trialToggle = document.getElementById('isTrialToggle');
            const trialDays = document.getElementById('trialDaysWrapper');
            const monthly = document.getElementById('monthlyWrapper');
            const monthlyInput = document.getElementById('agreed_monthly_price');
            const trial = document.getElementById('trialWrapper');

            trialToggle?.addEventListener('change', function () {
                trialDays.style.display = this.checked ? '' : 'none';
            });

            // Deal type: a setup-only deal has no monthly price and no trial.
            function applyMode() {
                const selected = document.querySelector('input[name="billing_mode"]:checked');
                const recurring = !selected || selected.value === 'recurring';
                monthly.style.display = recurring ? '' : 'none';
                trial.style.display = recurring ? '' : 'none';
                monthlyInput.required = recurring;
                // A setup-only deal must charge a setup fee — waiving it only makes sense with a retainer.
                const waive = document.getElementById('waiveHint');
                if (waive) waive.style.display = recurring ? '' : 'none';
            }

            document.querySelectorAll('input[name="billing_mode"]').forEach(function (r) {
                r.addEventListener('change', applyMode);
            });
        })();
    </script>
@endsection
