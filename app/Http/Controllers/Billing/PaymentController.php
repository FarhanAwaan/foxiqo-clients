<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\PaymentLink;
use App\Models\PaymentReceipt;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Show the payment page for a payment link.
     */
    public function show(string $token): View|RedirectResponse
    {
        $paymentLink = PaymentLink::where('payment_token', $token)
            ->with(['invoice.company', 'invoice.subscription.agent', 'invoice.subscription.plan', 'receipts'])
            ->firstOrFail();

        // Check if already paid
        if ($paymentLink->paid_at) {
            return view('billing.payment-completed', [
                'paymentLink' => $paymentLink,
                'invoice' => $paymentLink->invoice,
            ]);
        }

        // Check if expired
        if ($paymentLink->isExpired()) {
            return view('billing.payment-expired', [
                'paymentLink' => $paymentLink,
                'invoice' => $paymentLink->invoice,
            ]);
        }

        // Check if invoice is already paid (edge case: paid via another link)
        if ($paymentLink->invoice->status === 'paid') {
            return view('billing.payment-completed', [
                'paymentLink' => $paymentLink,
                'invoice' => $paymentLink->invoice,
            ]);
        }

        // Check if there's a pending receipt under review
        $pendingReceipt = $paymentLink->receipts()->pending()->latest()->first();
        if ($pendingReceipt) {
            return view('billing.receipt-pending', [
                'paymentLink' => $paymentLink,
                'invoice' => $paymentLink->invoice,
                'receipt' => $pendingReceipt,
            ]);
        }

        // Check if there's a rejected receipt (allow re-upload)
        $rejectedReceipt = $paymentLink->receipts()->rejected()->latest()->first();

        return view('billing.payment', [
            'paymentLink' => $paymentLink,
            'invoice' => $paymentLink->invoice,
            'rejectedReceipt' => $rejectedReceipt,
        ]);
    }

    /**
     * Process the payment submission.
     */
    public function process(Request $request, string $token): RedirectResponse
    {
        $paymentLink = PaymentLink::where('payment_token', $token)
            ->with('invoice')
            ->firstOrFail();

        // Validate payment link is still valid
        if ($paymentLink->paid_at) {
            return redirect()->route('billing.payment.show', $token)
                ->with('info', 'This payment has already been completed.');
        }

        if ($paymentLink->isExpired()) {
            return redirect()->route('billing.payment.show', $token)
                ->with('error', 'This payment link has expired.');
        }

        if ($paymentLink->invoice->status === 'paid') {
            return redirect()->route('billing.payment.show', $token)
                ->with('info', 'This invoice has already been paid.');
        }

        $request->validate([
            'payment_method' => 'required|string|in:card,bank_transfer',
        ]);

        if ($request->payment_method === 'bank_transfer') {
            return redirect()->route('billing.payment.bank-details', $token);
        }

        // For card payments, we'll need Stripe integration later
        return back()->with('error', 'Card payments are coming soon. Please use bank transfer.');
    }

    /**
     * Show bank transfer details.
     */
    public function bankDetails(string $token): View|RedirectResponse
    {
        $paymentLink = PaymentLink::where('payment_token', $token)
            ->with(['invoice.company', 'receipts'])
            ->firstOrFail();

        if ($paymentLink->paid_at || $paymentLink->invoice->status === 'paid') {
            return redirect()->route('billing.payment.show', $token);
        }

        if ($paymentLink->isExpired()) {
            return redirect()->route('billing.payment.show', $token);
        }

        // Check if there's a pending receipt
        $pendingReceipt = $paymentLink->receipts()->pending()->latest()->first();
        if ($pendingReceipt) {
            return redirect()->route('billing.payment.show', $token);
        }

        // Check for rejected receipt
        $rejectedReceipt = $paymentLink->receipts()->rejected()->latest()->first();

        return view('billing.bank-details', [
            'paymentLink' => $paymentLink,
            'invoice' => $paymentLink->invoice,
            'rejectedReceipt' => $rejectedReceipt,
        ]);
    }

    /**
     * Upload payment receipt.
     */
    public function uploadReceipt(Request $request, string $token): RedirectResponse
    {
        $paymentLink = PaymentLink::where('payment_token', $token)
            ->with('invoice')
            ->firstOrFail();

        // Validate payment link is still valid
        if ($paymentLink->paid_at || $paymentLink->invoice->status === 'paid') {
            return redirect()->route('billing.payment.show', $token)
                ->with('info', 'This invoice has already been paid.');
        }

        if ($paymentLink->isExpired()) {
            return redirect()->route('billing.payment.show', $token)
                ->with('error', 'This payment link has expired.');
        }

        // Check if there's already a pending receipt
        if ($paymentLink->hasPendingReceipt()) {
            return redirect()->route('billing.payment.show', $token)
                ->with('info', 'You already have a payment receipt pending review.');
        }

        $request->validate([
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'], // 10MB max
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $request->file('receipt');

        // Store the file
        $path = $file->store('payment-receipts/' . date('Y/m'), 'public');

        // Create the receipt record
        PaymentReceipt::create([
            'payment_link_id' => $paymentLink->id,
            'invoice_id' => $paymentLink->invoice_id,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'status' => 'pending',
            'customer_notes' => $request->input('notes'),
        ]);

        return redirect()->route('billing.payment.receipt-uploaded', $token);
    }

    /**
     * Show receipt uploaded (thank you) page.
     */
    public function receiptUploaded(string $token): View|RedirectResponse
    {
        $paymentLink = PaymentLink::where('payment_token', $token)
            ->with(['invoice.company', 'receipts'])
            ->firstOrFail();

        $latestReceipt = $paymentLink->receipts()->latest()->first();

        if (!$latestReceipt) {
            return redirect()->route('billing.payment.show', $token);
        }

        return view('billing.receipt-uploaded', [
            'paymentLink' => $paymentLink,
            'invoice' => $paymentLink->invoice,
            'receipt' => $latestReceipt,
        ]);
    }

    /**
     * Show payment success page.
     */
    public function success(string $token): View
    {
        $paymentLink = PaymentLink::where('payment_token', $token)
            ->with(['invoice.company', 'invoice.payments'])
            ->firstOrFail();

        return view('billing.payment-success', [
            'paymentLink' => $paymentLink,
            'invoice' => $paymentLink->invoice,
        ]);
    }
}
