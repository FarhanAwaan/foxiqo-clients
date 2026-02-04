@extends('layouts.admin')

@section('title', 'Plans')

@section('page-pretitle')
    Billing
@endsection

@section('page-header')
    Plans
@endsection

@section('page-actions')
    <a href="{{ route('admin.plans.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
        Add Plan
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Plans</h3>
            <div class="card-actions">
                <form action="{{ route('admin.plans.index') }}" method="GET" class="d-flex gap-2 flex-wrap">
                    <select name="type" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="standard" {{ request('type') == 'standard' ? 'selected' : '' }}>Standard</option>
                        <option value="custom" {{ request('type') == 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                    @if(request('type'))
                        <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Price</th>
                        <th>Included Minutes</th>
                        <th>Overage Rate</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Subscriptions</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td>
                                <a href="{{ route('admin.plans.show', $plan) }}" class="text-reset">
                                    <strong>{{ $plan->name }}</strong>
                                </a>
                                @if($plan->description)
                                    <div class="text-muted small text-truncate" style="max-width: 200px;">{{ $plan->description }}</div>
                                @endif
                            </td>
                            <td class="text-money">
                                ${{ number_format($plan->price, 2) }}<span class="text-muted">/mo</span>
                            </td>
                            <td>
                                {{ number_format($plan->included_minutes) }} min
                            </td>
                            <td>
                                @if($plan->overage_rate)
                                    ${{ number_format($plan->overage_rate, 4) }}<span class="text-muted">/min</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($plan->is_custom)
                                    <span class="badge bg-purple-lt">Custom</span>
                                    @if($plan->company)
                                        <div class="text-muted small">{{ $plan->company->name }}</div>
                                    @endif
                                @else
                                    <span class="badge bg-blue-lt">Standard</span>
                                @endif
                            </td>
                            <td>
                                @if($plan->is_active)
                                    <span class="badge bg-green-lt">Active</span>
                                @else
                                    <span class="badge bg-secondary-lt">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary text-white">{{ $plan->subscriptions_count ?? $plan->subscriptions()->count() }}</span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-icon btn-ghost-primary btn-md" data-bs-toggle="dropdown">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /></svg>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="{{ route('admin.plans.show', $plan) }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                                            View Details
                                        </a>
                                        <a class="dropdown-item" href="{{ route('admin.plans.edit', $plan) }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                                            Edit
                                        </a>
                                        @if(!$plan->subscriptions()->exists())
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this plan?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state py-4">
                                    <div class="empty-state-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg>
                                    </div>
                                    <p class="empty-state-title">No plans found</p>
                                    <p class="empty-state-description">Get started by creating your first plan.</p>
                                    <a href="{{ route('admin.plans.create') }}" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                                        Add Plan
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($plans->hasPages())
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Showing <span>{{ $plans->firstItem() }}</span> to <span>{{ $plans->lastItem() }}</span> of <span>{{ $plans->total() }}</span> entries
                </p>
                <div class="ms-auto">
                    {{ $plans->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
