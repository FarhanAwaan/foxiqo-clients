@extends('layouts.admin')

@section('title', 'Billing & Usage')

@section('page-pretitle')
    Billing &amp; Usage
@endsection

@section('page-header')
    Overview
@endsection

@section('content')
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.billing.index') }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="col-auto">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-auto">
                    <label class="form-label">Customer</label>
                    <select name="company_id" class="form-select">
                        <option value="">All Customers</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected($selectedCompany && $selectedCompany->id === $company->id)>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.billing.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if($selectedCompany)
        <div class="mb-3">
            <span class="badge bg-primary-lt">Showing: {{ $selectedCompany->name }}</span>
            <a href="{{ route('admin.billing.index', request()->except('company_id')) }}" class="ms-2">Clear customer filter</a>
        </div>
    @endif

    <!-- Summary Stats -->
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-2">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">MRR</div>
                    <div class="h1 mb-0">${{ number_format($mrr, 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Revenue (Period)</div>
                    <div class="h1 mb-0">${{ number_format($selectedCompany ? $companyStats['revenue'] : $systemStats['revenue'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Retell Cost</div>
                    <div class="h1 mb-0">${{ number_format($selectedCompany ? $companyStats['retell_cost'] : $systemStats['cost'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Profit</div>
                    @php $profit = $selectedCompany ? $companyStats['profit'] : $systemStats['profit']; @endphp
                    <div class="h1 mb-0 {{ $profit >= 0 ? 'text-green' : 'text-red' }}">${{ number_format($profit, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Margin</div>
                    @php $margin = $selectedCompany ? $companyStats['margin'] : $systemStats['margin']; @endphp
                    <div class="h1 mb-0 {{ $margin >= 0 ? 'text-green' : 'text-red' }}">{{ number_format($margin, 1) }}%</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Pending Payments</div>
                    <div class="h1 mb-0 {{ $systemStats['pending_payments'] > 0 ? 'text-yellow' : '' }}">${{ number_format($systemStats['pending_payments'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    @unless($selectedCompany)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Customers</h3>
                <div class="card-actions btn-list">
                    <a href="{{ route('admin.billing.index', array_merge(request()->query(), ['sort' => 'name'])) }}" class="btn btn-sm {{ $sort === 'name' ? 'btn-primary' : 'btn-outline-secondary' }}">Name</a>
                    <a href="{{ route('admin.billing.index', array_merge(request()->query(), ['sort' => 'mrr'])) }}" class="btn btn-sm {{ $sort === 'mrr' ? 'btn-primary' : 'btn-outline-secondary' }}">MRR</a>
                    <a href="{{ route('admin.billing.index', array_merge(request()->query(), ['sort' => 'usage'])) }}" class="btn btn-sm {{ $sort === 'usage' ? 'btn-primary' : 'btn-outline-secondary' }}">Usage</a>
                    <a href="{{ route('admin.billing.index', array_merge(request()->query(), ['sort' => 'margin'])) }}" class="btn btn-sm {{ $sort === 'margin' ? 'btn-primary' : 'btn-outline-secondary' }}">Margin</a>
                    <a href="{{ route('admin.billing.index', array_merge(request()->query(), ['sort' => 'overdue'])) }}" class="btn btn-sm {{ $sort === 'overdue' ? 'btn-primary' : 'btn-outline-secondary' }}">Overdue</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-hover">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Assistants</th>
                            <th>MRR</th>
                            <th>Usage This Period</th>
                            <th>Revenue</th>
                            <th>Retell Cost</th>
                            <th>Profit</th>
                            <th>Margin</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginatedRows as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.billing.index', array_merge(request()->query(), ['company_id' => $row->company->id])) }}" class="text-reset">
                                        <strong>{{ $row->company->name }}</strong>
                                    </a>
                                    @if($row->has_trial)
                                        <span class="badge bg-purple-lt ms-1">Trial</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $row->agents_count }}</td>
                                <td class="text-money">${{ number_format($row->mrr, 2) }}</td>
                                <td>
                                    {{ number_format($row->minutes_used, 1) }} min
                                    @if($row->usage_cost > 0)
                                        <span class="text-muted small">(${{ number_format($row->usage_cost, 2) }})</span>
                                    @endif
                                </td>
                                <td>${{ number_format($row->revenue, 2) }}</td>
                                <td>${{ number_format($row->retell_cost, 2) }}</td>
                                <td class="{{ $row->profit >= 0 ? 'text-green' : 'text-red' }}">${{ number_format($row->profit, 2) }}</td>
                                <td>
                                    <span class="badge {{ $row->margin >= 0 ? 'bg-green-lt' : 'bg-red-lt' }}">{{ number_format($row->margin, 1) }}%</span>
                                </td>
                                <td>
                                    @if($row->has_overdue)
                                        <span class="badge bg-red-lt">Overdue</span>
                                    @else
                                        <span class="badge bg-green-lt">Current</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No active customers yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($paginatedRows->hasPages())
                <div class="card-footer d-flex align-items-center">
                    <p class="m-0 text-muted">
                        Showing <span>{{ $paginatedRows->firstItem() }}</span> to <span>{{ $paginatedRows->lastItem() }}</span> of <span>{{ $paginatedRows->total() }}</span> entries
                    </p>
                    <div class="ms-auto">
                        {{ $paginatedRows->links() }}
                    </div>
                </div>
            @endif
        </div>
    @endunless

    <!-- Per-Agent Breakdown (when a customer is selected) -->
    @if($selectedCompany)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Margin by Agent &mdash; {{ $selectedCompany->name }}</h3>
                <div class="card-actions">
                    <a href="{{ route('admin.companies.show', $selectedCompany) }}" class="btn btn-ghost-primary btn-sm">View Customer</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Agent</th>
                            <th>Calls</th>
                            <th>Minutes</th>
                            <th>Revenue</th>
                            <th>Retell Cost</th>
                            <th>Profit</th>
                            <th>Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companyStats['agents'] ?? [] as $agentId => $agentStats)
                            @php $agent = $selectedCompany->agents->firstWhere('id', $agentId); @endphp
                            <tr>
                                <td>
                                    @if($agent)
                                        <a href="{{ route('admin.agents.show', $agent) }}">{{ $agent->name }}</a>
                                    @else
                                        Agent #{{ $agentId }}
                                    @endif
                                </td>
                                <td>{{ number_format($agentStats['total_calls']) }}</td>
                                <td>{{ number_format($agentStats['total_minutes'], 1) }}</td>
                                <td>${{ number_format($agentStats['revenue'], 2) }}</td>
                                <td>${{ number_format($agentStats['retell_cost'], 2) }}</td>
                                <td class="{{ $agentStats['profit'] >= 0 ? 'text-green' : 'text-red' }}">
                                    ${{ number_format($agentStats['profit'], 2) }}
                                </td>
                                <td>
                                    <span class="badge {{ $agentStats['margin'] >= 0 ? 'bg-green-lt' : 'bg-red-lt' }}">
                                        {{ number_format($agentStats['margin'], 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No agents for this customer</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
