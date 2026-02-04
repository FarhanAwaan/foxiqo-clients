@extends('layouts.admin')

@section('title', 'Review Receipt')

@section('page-pretitle')
    Payment Receipts
@endsection

@section('page-header')
    Review Receipt
@endsection

@section('page-actions')
    @if($receipt->status === 'pending')
        <div class="btn-list">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                Approve & Mark Paid
            </button>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                Reject
            </button>
        </div>
    @endif
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <!-- Receipt Preview -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Receipt File</h3>
                    <div class="card-actions">
                        <a href="{{ route('admin.receipts.download', $receipt) }}" class="btn btn-sm btn-outline-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
                            Download
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(in_array($receipt->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']))
                        <div class="text-center">
                            <img src="{{ Storage::disk('public')->url($receipt->file_path) }}" alt="Payment Receipt" class="img-fluid rounded" style="max-height: 600px;">
                        </div>
                    @elseif($receipt->mime_type === 'application/pdf')
                        <div class="ratio ratio-16x9" style="min-height: 500px;">
                            <iframe src="{{ Storage::disk('public')->url($receipt->file_path) }}" class="rounded"></iframe>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-muted mb-3" width="48" height="48" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /></svg>
                            <p class="text-muted">Preview not available for this file type.</p>
                            <a href="{{ route('admin.receipts.download', $receipt) }}" class="btn btn-primary">Download File</a>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="row text-muted small">
                        <div class="col-auto">
                            <strong>Filename:</strong> {{ $receipt->original_filename }}
                        </div>
                        <div class="col-auto">
                            <strong>Size:</strong> {{ $receipt->getFormattedFileSize() }}
                        </div>
                        <div class="col-auto">
                            <strong>Type:</strong> {{ $receipt->mime_type }}
                        </div>
                    </div>
                </div>
            </div>

            @if($receipt->customer_notes)
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Customer Notes</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $receipt->customer_notes }}</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Status Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Receipt Status</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3 text-center">
                        @switch($receipt->status)
                            @case('pending')
                                <span class="badge bg-yellow-lt fs-5 px-3 py-2">Pending Review</span>
                                @break
                            @case('approved')
                                <span class="badge bg-green-lt fs-5 px-3 py-2">Approved</span>
                                @break
                            @case('rejected')
                                <span class="badge bg-red-lt fs-5 px-3 py-2">Rejected</span>
                                @break
                        @endswitch
                    </div>

                    @if($receipt->reviewed_at)
                        <div class="text-muted small text-center">
                            Reviewed {{ $receipt->reviewed_at->diffForHumans() }}
                            @if($receipt->reviewer)
                                by {{ $receipt->reviewer->name }}
                            @endif
                        </div>
                    @endif

                    @if($receipt->status === 'rejected' && $receipt->rejection_reason)
                        <div class="alert alert-danger mt-3 mb-0">
                            <strong>Rejection Reason:</strong><br>
                            {{ $receipt->rejection_reason }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Invoice Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Invoice Details</h3>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Invoice Number</div>
                            <div class="datagrid-content">
                                <a href="{{ route('admin.invoices.show', $receipt->invoice) }}">
                                    {{ $receipt->invoice->invoice_number }}
                                </a>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Amount</div>
                            <div class="datagrid-content text-money h3 mb-0">${{ number_format($receipt->invoice->amount, 2) }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Invoice Status</div>
                            <div class="datagrid-content">
                                @switch($receipt->invoice->status)
                                    @case('paid')
                                        <span class="badge bg-green-lt">Paid</span>
                                        @break
                                    @case('sent')
                                        <span class="badge bg-blue-lt">Sent</span>
                                        @break
                                    @case('overdue')
                                        <span class="badge bg-red-lt">Overdue</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary-lt">{{ ucfirst($receipt->invoice->status) }}</span>
                                @endswitch
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Company</div>
                            <div class="datagrid-content">
                                @if($receipt->invoice->company)
                                    <a href="{{ route('admin.companies.show', $receipt->invoice->company) }}">
                                        {{ $receipt->invoice->company->name }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        @if($receipt->invoice->subscription)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Agent</div>
                                <div class="datagrid-content">
                                    @if($receipt->invoice->subscription->agent)
                                        <a href="{{ route('admin.agents.show', $receipt->invoice->subscription->agent) }}">
                                            {{ $receipt->invoice->subscription->agent->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Plan</div>
                                <div class="datagrid-content">
                                    {{ $receipt->invoice->subscription->plan->name ?? '-' }}
                                </div>
                            </div>
                        @endif
                        <div class="datagrid-item">
                            <div class="datagrid-title">Uploaded</div>
                            <div class="datagrid-content">{{ $receipt->created_at->format('M d, Y h:i A') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Links</h3>
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('admin.invoices.show', $receipt->invoice) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /></svg>
                        View Invoice
                    </a>
                    @if($receipt->invoice->company)
                        <a href="{{ route('admin.companies.show', $receipt->invoice->company) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M9 8l1 0" /><path d="M9 12l1 0" /><path d="M9 16l1 0" /><path d="M14 8l1 0" /><path d="M14 12l1 0" /><path d="M14 16l1 0" /><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" /></svg>
                            View Company
                        </a>
                    @endif
                    <a href="{{ route('admin.receipts.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /></svg>
                        All Receipts
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    @if($receipt->status === 'pending')
        <div class="modal modal-blur fade" id="approveModal" tabindex="-1">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('admin.receipts.approve', $receipt) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Approve Receipt</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to approve this receipt?</p>
                            <p class="text-muted">This will mark invoice <strong>{{ $receipt->invoice->invoice_number }}</strong> as paid (<strong>${{ number_format($receipt->invoice->amount, 2) }}</strong>) and activate the subscription.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success ms-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                Approve & Mark Paid
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div class="modal modal-blur fade" id="rejectModal" tabindex="-1">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('admin.receipts.reject', $receipt) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Reject Receipt</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Reject the receipt for invoice <strong>{{ $receipt->invoice->invoice_number }}</strong>?</p>
                            <p class="text-muted small">The customer will be able to upload a new receipt after rejection.</p>
                            <div class="mb-3">
                                <label class="form-label required">Rejection Reason</label>
                                <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Explain why the receipt is being rejected..."></textarea>
                                <div class="form-hint">This reason will be shown to the customer.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger ms-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                                Reject Receipt
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
