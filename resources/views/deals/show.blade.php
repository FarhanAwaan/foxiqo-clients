@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.closer')

@section('title', $deal->business_name)

@section('page-pretitle')
    Deal
@endsection

@section('page-header')
    {{ $deal->business_name }}
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            @if($deal->status === 'sent')
                <div class="card card-md bg-primary-lt mb-4">
                    <div class="card-body p-4">
                        <h3 class="mb-1">Checkout Link</h3>
                        <p class="text-muted small mb-2">Send this to {{ $deal->customer_name }} to complete payment.</p>
                        @php $checkoutUrl = route('billing.deal.show', $deal); @endphp
                        <div class="input-group">
                            <input type="text" class="form-control" id="checkoutUrl" value="{{ $checkoutUrl }}" readonly>
                            <button class="btn btn-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('checkoutUrl').value)">
                                Copy Link
                            </button>
                            <a href="{{ $checkoutUrl }}" target="_blank" class="btn btn-outline-primary">Open</a>
                        </div>
                    </div>
                </div>
            @elseif($deal->status === 'paid')
                <div class="alert alert-success mb-4">
                    <strong>Paid.</strong> The client's account has been provisioned and an invitation email sent to {{ $deal->email }}.
                    @if($deal->is_trial)
                        Only the ${{ number_format($deal->activation_price, 2) }} activation fee was charged —
                        Paddle won't bill the ${{ number_format($deal->agreed_monthly_price, 2) }}/mo until the
                        {{ $deal->trial_days }}-day trial ends.
                    @endif
                    @if($deal->isClaimed())
                        <div class="mt-2">
                            Applied to
                            <a href="{{ route('admin.subscriptions.show', $deal->subscription) }}" class="alert-link">this subscription</a>.
                        </div>
                    @elseif(auth()->user()->isAdmin() && $deal->company)
                        <div class="mt-2">
                            <a href="{{ route('admin.agents.create', ['company_id' => $deal->company->id, 'deal_id' => $deal->id]) }}" class="btn btn-sm btn-primary">
                                Add Assistant for {{ $deal->company->name }}
                            </a>
                        </div>
                    @endif
                </div>
            @elseif($deal->status === 'expired')
                <div class="alert alert-danger mb-4">
                    This deal's checkout link expired or payment failed. Create a new deal to try again.
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Deal Details</h3>
                    <div class="card-actions">
                        @switch($deal->status)
                            @case('paid')
                                <span class="badge bg-green-lt fs-6">Paid</span>
                                @break
                            @case('expired')
                                <span class="badge bg-red-lt fs-6">Expired</span>
                                @break
                            @default
                                <span class="badge bg-blue-lt fs-6">Sent</span>
                        @endswitch
                    </div>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Contact</div>
                            <div class="datagrid-content">{{ $deal->customer_name }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Email</div>
                            <div class="datagrid-content">{{ $deal->email }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Phone</div>
                            <div class="datagrid-content">{{ $deal->phone ?: '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Industry</div>
                            <div class="datagrid-content">{{ $deal->industry ?: '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Location</div>
                            <div class="datagrid-content">{{ collect([$deal->city, $deal->country])->filter()->implode(', ') ?: '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Website</div>
                            <div class="datagrid-content">{{ $deal->website ?: '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Monthly Price</div>
                            <div class="datagrid-content text-money">${{ number_format($deal->agreed_monthly_price, 2) }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Activation Fee</div>
                            <div class="datagrid-content text-money">${{ number_format($deal->activation_price, 2) }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Trial</div>
                            <div class="datagrid-content">
                                @if($deal->is_trial)
                                    <span class="badge bg-purple-lt">{{ $deal->trial_days }} days</span>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Summary</h3>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        @if(auth()->user()->can('deals.view-all'))
                            <div class="datagrid-item">
                                <div class="datagrid-title">Closer</div>
                                <div class="datagrid-content">{{ $deal->closer->full_name ?? '-' }}</div>
                            </div>
                        @endif
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">{{ $deal->created_at->format('M d, Y h:i A') }}</div>
                        </div>
                        @if($deal->company)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Customer</div>
                                <div class="datagrid-content">
                                    @if(auth()->user()->isAdmin())
                                        <a href="{{ route('admin.companies.show', $deal->company) }}">{{ $deal->company->name }}</a>
                                    @else
                                        {{ $deal->company->name }}
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
