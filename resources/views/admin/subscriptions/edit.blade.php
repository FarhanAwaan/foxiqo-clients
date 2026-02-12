@extends('layouts.admin')

@section('title', 'Edit Subscription')

@section('page-pretitle')
    Subscriptions
@endsection

@section('page-header')
    Edit Subscription
@endsection

@section('content')
    <form action="{{ route('admin.subscriptions.update', $subscription) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Subscription Details</h3>
                    </div>
                    <div class="card-body">
                        <!-- Assistant Info (Read-only) -->
                        <div class="mb-3">
                            <label class="form-label">Assistant</label>
                            <div class="form-control-plaintext">
                                <strong>{{ $subscription->agent?->name }}</strong>
                                @if($subscription->agent?->phone_number)
                                    <span class="text-muted">({{ $subscription->agent->phone_number }})</span>
                                @endif
                            </div>
                            <small class="text-muted">Assistant cannot be changed. Create a new subscription for a different assistant.</small>
                        </div>

                        <!-- Company Info (Read-only) -->
                        <div class="mb-3">
                            <label class="form-label">Company</label>
                            <div class="form-control-plaintext">
                                <a href="{{ route('admin.companies.show', $subscription->company) }}">
                                    {{ $subscription->company?->name }}
                                </a>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="mb-3">
                            <label class="form-label required">Plan</label>
                            <select name="plan_id" id="planSelect" class="form-select @error('plan_id') is-invalid @enderror" required>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}"
                                            data-price="{{ $plan->price }}"
                                            data-minutes="{{ $plan->included_minutes }}"
                                            {{ old('plan_id', $subscription->plan_id) == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }} - ${{ number_format($plan->price, 2) }}/mo ({{ number_format($plan->included_minutes) }} min)
                                    </option>
                                @endforeach
                            </select>
                            @error('plan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Custom Price (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="custom_price" step="0.01" min="0"
                                       class="form-control @error('custom_price') is-invalid @enderror"
                                       value="{{ old('custom_price', $subscription->custom_price) }}" placeholder="Leave blank to use plan price">
                                <span class="input-group-text">/mo</span>
                            </div>
                            <small class="text-muted">Override the plan's default price for this subscription</small>
                            @error('custom_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Status</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            @switch($subscription->status)
                                @case('active')
                                    <span class="badge bg-green-lt fs-5">Active</span>
                                    @break
                                @case('pending')
                                    <span class="badge bg-yellow-lt fs-5">Pending Activation</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-red-lt fs-5">Cancelled</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary-lt fs-5">{{ ucfirst($subscription->status) }}</span>
                            @endswitch
                        </div>

                        @if($subscription->current_period_start && $subscription->current_period_end)
                            <div class="datagrid">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Current Period</div>
                                    <div class="datagrid-content">
                                        {{ $subscription->current_period_start->format('M d') }} - {{ $subscription->current_period_end->format('M d, Y') }}
                                    </div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Minutes Used</div>
                                    <div class="datagrid-content">
                                        {{ number_format($subscription->minutes_used ?? 0) }} / {{ number_format($subscription->plan->included_minutes ?? 0) }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($subscription->circuit_breaker_triggered)
                            <div class="alert alert-danger mt-3 mb-0">
                                <div class="d-flex">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" /></svg>
                                    </div>
                                    <div>
                                        <h4 class="alert-title">Circuit Breaker Triggered</h4>
                                        <div class="text-muted small">
                                            Triggered at: {{ $subscription->circuit_breaker_triggered_at?->format('M d, Y h:i A') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        <div class="d-flex">
                            <a href="{{ route('admin.subscriptions.show', $subscription) }}" class="btn btn-outline-secondary">Back</a>
                            <button type="submit" class="btn btn-primary ms-auto">
                                Update Subscription
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Actions</h3>
                    </div>
                    <div class="card-body">
                        @if($subscription->status === 'pending')
                            <form action="{{ route('admin.subscriptions.activate', $subscription) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100 mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                    Activate Subscription
                                </button>
                            </form>
                        @endif

                        @if($subscription->status === 'active')
                            <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                                Cancel Subscription
                            </button>
                        @endif

                        @if($subscription->status !== 'active')
                            <form action="{{ route('admin.subscriptions.destroy', $subscription) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this subscription?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                    Delete Subscription
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Cancel Modal -->
    @if($subscription->status === 'active')
        <div class="modal modal-blur fade" id="cancelModal" tabindex="-1">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('admin.subscriptions.cancel', $subscription) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Cancel Subscription</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to cancel this subscription?</p>
                            <div class="mb-3">
                                <label class="form-label">Reason (optional)</label>
                                <textarea name="reason" class="form-control" rows="2" placeholder="Enter cancellation reason..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                                Keep Subscription
                            </button>
                            <button type="submit" class="btn btn-danger ms-auto">
                                Cancel Subscription
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
