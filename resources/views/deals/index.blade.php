@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.closer')

@section('title', 'Deals')

@section('page-header')
    {{ auth()->user()->can('deals.view-all') ? 'All Deals' : 'My Deals' }}
@endsection

@section('page-actions')
    <a href="{{ route('deals.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
        New Deal
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Business</th>
                        <th>Customer</th>
                        @if(auth()->user()->can('deals.view-all'))
                            <th>Closer</th>
                        @endif
                        <th>Price</th>
                        <th>Status</th>
                        <th>Billing</th>
                        <th>Created</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deals as $deal)
                        <tr>
                            <td>
                                <a href="{{ route('deals.show', $deal) }}" class="text-reset">
                                    <strong>{{ $deal->business_name }}</strong>
                                </a>
                            </td>
                            <td>
                                {{ $deal->customer_name }}
                                <div class="text-muted small">{{ $deal->email }}</div>
                            </td>
                            @if(auth()->user()->can('deals.view-all'))
                                <td>{{ $deal->closer->full_name ?? '-' }}</td>
                            @endif
                            <td>{{ \App\Support\DealTerms::summary($deal) }}</td>
                            <td>
                                @switch($deal->status)
                                    @case('paid')
                                        <span class="badge bg-green-lt">Paid</span>
                                        @break
                                    @case('expired')
                                        <span class="badge bg-red-lt">Expired</span>
                                        @break
                                    @default
                                        <span class="badge bg-blue-lt">Sent</span>
                                @endswitch
                            </td>
                            <td>
                                @if($deal->paddle_status_label)
                                    <span class="badge {{ $deal->paddle_status_badge }}">{{ $deal->paddle_status_label }}</span>
                                    @if($deal->isTrialing() && $deal->trial_ends_at)
                                        <div class="text-muted small">charges {{ \App\Support\BillingTime::date($deal->trial_ends_at) }}</div>
                                    @elseif($deal->paddle_status === 'active' && $deal->next_billed_at)
                                        <div class="text-muted small">next {{ \App\Support\BillingTime::date($deal->next_billed_at) }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $deal->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('deals.show', $deal) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state py-4">
                                    <div class="empty-state-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17l6 -6l4 4l8 -8" /><path d="M14 7l7 0l0 7" /></svg>
                                    </div>
                                    <p class="empty-state-title">No deals yet</p>
                                    <p class="empty-state-description">Create your first deal to get a checkout link you can send during a call.</p>
                                    <a href="{{ route('deals.create') }}" class="btn btn-primary">New Deal</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($deals->hasPages())
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Showing <span>{{ $deals->firstItem() }}</span> to <span>{{ $deals->lastItem() }}</span> of <span>{{ $deals->total() }}</span> entries
                </p>
                <div class="ms-auto">
                    {{ $deals->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
