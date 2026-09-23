@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.closer')

@section('title', 'Customers')

@section('page-header')
    Customers
@endsection

@section('content')
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Assistants</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                        <tr>
                            <td>
                                <a href="{{ route('closer.companies.show', $company) }}" class="text-reset">
                                    <strong>{{ $company->name }}</strong>
                                </a>
                            </td>
                            <td class="text-muted">{{ $company->agents_count }}</td>
                            <td>
                                <a href="{{ route('closer.companies.show', $company) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="empty-state py-4">
                                    <div class="empty-state-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M9 8l1 0" /><path d="M9 12l1 0" /><path d="M9 16l1 0" /><path d="M14 8l1 0" /><path d="M14 12l1 0" /><path d="M14 16l1 0" /><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" /></svg>
                                    </div>
                                    <p class="empty-state-title">No customers to show yet</p>
                                    <p class="empty-state-description">Ask an admin to grant you access to a customer from Admin &gt; Users.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($companies->hasPages())
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Showing <span>{{ $companies->firstItem() }}</span> to <span>{{ $companies->lastItem() }}</span> of <span>{{ $companies->total() }}</span> entries
                </p>
                <div class="ms-auto">
                    {{ $companies->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
