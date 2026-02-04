<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PaymentReceipt;
use App\Services\AuditService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PaymentReceiptController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected AuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $query = PaymentReceipt::with(['invoice.company', 'paymentLink', 'reviewer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to showing pending receipts first
            $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END");
        }

        if ($request->filled('company_id')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        $receipts = $query->latest()->paginate(15)->withQueryString();
        $companies = Company::orderBy('name')->get();

        $pendingCount = PaymentReceipt::pending()->count();

        return view('admin.receipts.index', compact('receipts', 'companies', 'pendingCount'));
    }

    public function show(PaymentReceipt $receipt): View
    {
        $receipt->load([
            'invoice.company',
            'invoice.subscription.agent',
            'invoice.subscription.plan',
            'paymentLink',
            'reviewer',
        ]);

        return view('admin.receipts.show', compact('receipt'));
    }

    public function approve(PaymentReceipt $receipt): RedirectResponse
    {
        if ($receipt->status !== 'pending') {
            return back()->with('error', 'This receipt has already been reviewed.');
        }

        $oldValues = $receipt->toArray();

        $receipt->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        // Mark the invoice as paid
        $this->invoiceService->markAsPaid(
            $receipt->invoice,
            'bank_transfer',
            'Receipt #' . $receipt->uuid,
            $receipt->paymentLink
        );

        $this->auditService->log('receipt_approved', $receipt, $oldValues);

        return redirect()->route('admin.receipts.index')
            ->with('success', 'Receipt approved and invoice marked as paid.');
    }

    public function reject(Request $request, PaymentReceipt $receipt): RedirectResponse
    {
        if ($receipt->status !== 'pending') {
            return back()->with('error', 'This receipt has already been reviewed.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $oldValues = $receipt->toArray();

        $receipt->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $this->auditService->log('receipt_rejected', $receipt, $oldValues);

        return redirect()->route('admin.receipts.index')
            ->with('success', 'Receipt rejected. Customer can upload a new receipt.');
    }

    /**
     * Download the receipt file.
     */
    public function download(PaymentReceipt $receipt)
    {
        if (!Storage::disk('public')->exists($receipt->file_path)) {
            return back()->with('error', 'Receipt file not found.');
        }

        return Storage::disk('public')->download(
            $receipt->file_path,
            $receipt->original_filename
        );
    }
}
