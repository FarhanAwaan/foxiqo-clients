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

        // Over the WHOLE filtered set, not the 15 rows on the current page — the previous version
        // summed $invoices->sum('amount') on the already-paginated collection, so every card here
        // only ever reflected whatever happened to be on screen. paddle_charged_total is what Paddle's
        // own transactions actually charged (App\Support\PaddleMoney), for every invoice that went
        // through it — the number to hold up against Paddle's dashboard; paddle_recorded_total is our
        // own `amount` for the same invoices, so the two totals are directly comparable.
        $stats = (clone $query)->selectRaw(
            "SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
             SUM(CASE WHEN status IN ('sent', 'draft') THEN amount ELSE 0 END) as pending_amount,
             SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END) as overdue_amount,
             SUM(CASE WHEN paddle_transaction_id IS NOT NULL THEN amount ELSE 0 END) as paddle_recorded_total,
             SUM(CASE WHEN paddle_transaction_id IS NOT NULL THEN paddle_charged_amount ELSE 0 END) as paddle_charged_total,
             SUM(paddle_refunded_amount) as paddle_refunded_total,
             COUNT(CASE WHEN paddle_charged_amount IS NOT NULL
                AND ABS(paddle_charged_amount - (amount + COALESCE(paddle_tax_amount, 0))) >= 0.01
                THEN 1 END) as paddle_mismatch_count"
        )->first();

        $invoices = $query->latest()->paginate(15)->withQueryString();
        $companies = Company::orderBy('name')->get();

        return view('admin.invoices.index', compact('invoices', 'companies', 'stats'));
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
