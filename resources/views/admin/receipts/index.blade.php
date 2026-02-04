@extends('layouts.admin')

@section('title', 'Payment Receipts')

@section('page-pretitle')
    Billing
@endsection

@section('page-header')
    Payment Receipts
@endsection

@section('content')
    @if($pendingCount > 0)
        <div class="alert alert-warning mb-4">
            <div class="d-flex">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 8v4" /><path d="M12 16h.01" /></svg>
                </div>
                <div>
                    <strong>{{ $pendingCount }} receipt{{ $pendingCount > 1 ? 's' : '' }} pending review.</strong>
                    These receipts need your attention to approve or reject.
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">All Receipts</h3>
            <div class="card-actions">
                <form action="{{ route('admin.receipts.index') }}" method="GET" class="d-flex gap-2 flex-wrap">
                    <select name="company_id" class="form-select form-select-sm" style="width: 180px;" onchange="this.form.submit()">
                        <option value="">All Companies</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select form-select-sm" style="width: 140px;" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                    @if(request('company_id') || request('status'))
                        <a href="{{ route('admin.receipts.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Company</th>
                        <th>File</th>
                        <th>Amount</th>
                        <th>Uploaded</th>
                        <th>Status</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td>
                                <a href="{{ route('admin.invoices.show', $receipt->invoice) }}" class="text-reset">
                                    <strong>{{ $receipt->invoice->invoice_number }}</strong>
                                </a>
                            </td>
                            <td>
                                @if($receipt->invoice->company)
                                    <a href="{{ route('admin.companies.show', $receipt->invoice->company) }}" class="text-reset">
                                        {{ $receipt->invoice->company->name }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ Str::limit($receipt->original_filename, 30) }}</div>
                                <div class="text-muted small">{{ $receipt->getFormattedFileSize() }}</div>
                            </td>
                            <td class="text-money">
                                <strong>${{ number_format($receipt->invoice->amount, 2) }}</strong>
                            </td>
                            <td>
                                <span>{{ $receipt->created_at->format('M d, Y') }}</span>
                                <div class="text-muted small">{{ $receipt->created_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                @switch($receipt->status)
                                    @case('pending')
                                        <span class="badge bg-yellow-lt">Pending</span>
                                        @break
                                    @case('approved')
                                        <span class="badge bg-green-lt">Approved</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge bg-red-lt">Rejected</span>
                                        @break
                                @endswitch
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="{{ route('admin.receipts.show', $receipt) }}" class="btn btn-sm btn-outline-primary">
                                        Review
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state py-4">
                                    <div class="empty-state-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2" /><path d="M14 8h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5" /><path d="M12 6v2" /><path d="M12 14v2" /></svg>
                                    </div>
                                    <p class="empty-state-title">No receipts found</p>
                                    <p class="empty-state-description">Payment receipts will appear here when customers upload them.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($receipts->hasPages())
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Showing <span>{{ $receipts->firstItem() }}</span> to <span>{{ $receipts->lastItem() }}</span> of <span>{{ $receipts->total() }}</span> entries
                </p>
                <div class="ms-auto">
                    {{ $receipts->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
