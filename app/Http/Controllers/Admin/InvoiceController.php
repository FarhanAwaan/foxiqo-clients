<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\PaddleService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function index(Request $request): View
    {
        $query = Invoice::with(['company', 'subscription.agent']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('subscription_id')) {
            $query->where('subscription_id', $request->subscription_id);
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();
        $companies = Company::orderBy('name')->get();

        return view('admin.invoices.index', compact('invoices', 'companies'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['company', 'subscription.agent', 'subscription.plan', 'paymentLinks', 'payments', 'receipts.reviewer']);

        $activity = AuditLog::where('entity_type', Invoice::class)
            ->where('entity_id', $invoice->id)
            ->with('user')
            ->latest()
            ->get();

        return view('admin.invoices.show', compact('invoice', 'activity'));
    }

    /**
     * Paddle's invoice PDF link expires after an hour, so this is fetched fresh
     * on every click and redirected straight through — never cached or stored.
     */
    public function paddleInvoice(Invoice $invoice, PaddleService $paddleService): RedirectResponse
    {
        if (!$invoice->paddle_transaction_id) {
            return back()->with('error', 'This invoice has no associated Paddle transaction.');
        }

        $url = $paddleService->getTransactionInvoiceUrl($invoice->paddle_transaction_id);

        if (!$url) {
            return back()->with('error', 'Paddle did not return an invoice link for this transaction.');
        }

        return redirect()->away($url);
    }

    public function sendPaymentLink(Invoice $invoice): RedirectResponse
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice is already paid.');
        }

        try {
            $this->invoiceService->sendPaymentLink($invoice, manual: true);

            return back()->with('success', 'Payment link sent successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send payment link: ' . $e->getMessage());
        }
    }

    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice is already paid.');
        }

        $validated = $request->validate([
            'provider' => ['required', 'in:internal,bank_transfer,paddle,stripe,manual'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ]);

        $this->invoiceService->markAsPaid(
            $invoice,
            $validated['provider'],
            $validated['transaction_id'] ?? null
        );

        return back()->with('success', 'Invoice marked as paid.');
    }
}
