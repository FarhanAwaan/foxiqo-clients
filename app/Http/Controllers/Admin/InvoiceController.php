<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\InvoiceService;
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

        $invoices = $query->latest()->paginate(15)->withQueryString();
        $companies = Company::orderBy('name')->get();

        return view('admin.invoices.index', compact('invoices', 'companies'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['company', 'subscription.agent', 'subscription.plan', 'paymentLinks', 'payments']);

        return view('admin.invoices.show', compact('invoice'));
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
            'provider' => ['required', 'in:payoneer,stripe,manual'],
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
