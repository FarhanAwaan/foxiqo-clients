<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the customer's invoices.
     */
    public function index(Request $request): View
    {
        $company = auth()->user()->company;

        $query = Invoice::where('company_id', $company->id)
            ->with(['subscription.agent', 'subscription.plan'])
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->paginate(15)->withQueryString();

        // Get summary statistics
        $stats = [
            'total' => Invoice::where('company_id', $company->id)->count(),
            'paid' => Invoice::where('company_id', $company->id)->where('status', 'paid')->count(),
            'pending' => Invoice::where('company_id', $company->id)->whereIn('status', ['draft', 'sent'])->count(),
            'overdue' => Invoice::where('company_id', $company->id)
                ->where('status', '!=', 'paid')
                ->where('due_date', '<', now())
                ->count(),
            'total_paid' => Invoice::where('company_id', $company->id)->where('status', 'paid')->sum('amount'),
        ];

        return view('customer.invoices.index', compact('invoices', 'stats'));
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): View
    {
        $company = auth()->user()->company;

        // Ensure the invoice belongs to the customer's company
        if ($invoice->company_id !== $company->id) {
            abort(403, 'Unauthorized access to this invoice.');
        }

        $invoice->load([
            'subscription.agent',
            'subscription.plan',
            'payments',
            'paymentLinks' => function ($query) {
                $query->latest();
            },
        ]);

        // Get active payment link if exists
        $activePaymentLink = $invoice->paymentLinks
            ->first(fn($link) => $link->isActive());

        return view('customer.invoices.show', compact('invoice', 'activePaymentLink'));
    }
}
