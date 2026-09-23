@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.closer')

@section('title', $company->name)

@section('page-pretitle')
    Customers
@endsection

@section('page-header')
    {{ $company->name }}
@endsection

@section('page-actions')
    <a href="{{ route('closer.companies.index') }}" class="btn btn-outline-secondary">
        Back to Customers
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="datagrid">
                        @if($company->city || $company->country)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Location</div>
                                <div class="datagrid-content">{{ collect([$company->city, $company->country])->filter()->implode(', ') }}</div>
                            </div>
                        @endif
                        @if($company->phone)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Phone</div>
                                <div class="datagrid-content">{{ $company->phone }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Assistants</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Assistant</th>
                                <th>Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agents as $agent)
                                <tr>
                                    <td>
                                        <a href="{{ route('closer.agents.show', $agent) }}" class="text-reset">
                                            <strong>{{ $agent->name }}</strong>
                                        </a>
                                        @if($agent->phone_number)
                                            <div class="text-muted small">{{ $agent->phone_number }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($agent->status === 'active')
                                            <span class="badge bg-green-lt">Active</span>
                                        @else
                                            <span class="badge bg-secondary-lt">{{ ucfirst($agent->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('closer.agents.show', $agent) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No assistants visible for this customer</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
