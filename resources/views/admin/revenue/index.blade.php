@extends('layouts.admin')

@section('title', 'Revenue')

@section('page-pretitle')
    Reporting
@endsection

@section('page-header')
    Revenue
@endsection

@section('content')
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.revenue.index') }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="col-auto">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-auto">
                    <label class="form-label">Company</label>
                    <select name="company_id" class="form-select">
                        <option value="">All Companies</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected($selectedCompany && $selectedCompany->id === $company->id)>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.revenue.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if($selectedCompany)
        <div class="mb-3">
            <span class="badge bg-primary-lt">Showing: {{ $selectedCompany->name }}</span>
            <a href="{{ route('admin.revenue.index', request()->except('company_id')) }}" class="ms-2">Clear company filter</a>
        </div>
    @endif

    <!-- Summary Stats (portfolio-wide, or scoped to the selected company) -->
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Revenue</div>
                    <div class="h1 mb-0">${{ number_format($summaryStats['revenue'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Retell Cost</div>
                    <div class="h1 mb-0">${{ number_format($summaryStats['cost'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Profit</div>
                    <div class="h1 mb-0 {{ $summaryStats['profit'] >= 0 ? 'text-green' : 'text-red' }}">
                        ${{ number_format($summaryStats['profit'], 2) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Avg. Profit Margin</div>
                    <div class="h1 mb-0 {{ $summaryStats['margin'] >= 0 ? 'text-green' : 'text-red' }}">
                        {{ number_format($summaryStats['margin'], 1) }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Per-Company Margin Breakdown (hidden once drilled into a single company) -->
    @unless($selectedCompany)
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Margin by Company</h3>
                <div class="card-subtitle">Lowest margin first &mdash; the accounts most worth a closer look</div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Revenue</th>
                            <th>Retell Cost</th>
                            <th>Profit</th>
                            <th>Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companyBreakdown as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.revenue.index', array_merge(request()->query(), ['company_id' => $row['company']->id])) }}">
                                        {{ $row['company']->name }}
                                    </a>
                                </td>
                                <td>${{ number_format($row['revenue'], 2) }}</td>
                                <td>${{ number_format($row['retell_cost'], 2) }}</td>
                                <td class="{{ $row['profit'] >= 0 ? 'text-green' : 'text-red' }}">
                                    ${{ number_format($row['profit'], 2) }}
                                </td>
                                <td>
                                    <span class="badge {{ $row['margin'] >= 0 ? 'bg-green-lt' : 'bg-red-lt' }}">
                                        {{ number_format($row['margin'], 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No active companies in this period</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endunless

    <!-- Per-Agent Breakdown (when a company is selected) -->
    @if($selectedCompany)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Margin by Agent &mdash; {{ $selectedCompany->name }}</h3>
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
                                <td colspan="7" class="text-center text-muted">No agents for this company</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
