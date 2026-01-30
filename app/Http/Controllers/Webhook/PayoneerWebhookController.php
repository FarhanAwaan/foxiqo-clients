<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentLink;
use App\Models\WebhookLog;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PayoneerWebhookController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function handle(Request $request): Response
    {
        $webhookLog = WebhookLog::create([
            'source' => 'payoneer',
            'event_type' => $request->input('event_type', 'unknown'),
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
            'status' => 'received',
        ]);

        try {
            $this->processWebhook($webhookLog);
            $webhookLog->markProcessed();
        } catch (\Exception $e) {
            $webhookLog->markFailed($e->getMessage());
        }

        return response('OK', 200);
    }

    protected function processWebhook(WebhookLog $webhookLog): void
    {
        $payload = $webhookLog->payload;
        $eventType = $payload['event_type'] ?? null;

        match ($eventType) {
            'payment_completed' => $this->handlePaymentCompleted($payload),
            'payment_failed' => $this->handlePaymentFailed($payload),
            default => null,
        };
    }

    protected function handlePaymentCompleted(array $payload): void
    {
        $reference = $payload['reference'] ?? null;
        $transactionId = $payload['transaction_id'] ?? null;

        if (!$reference) {
            throw new \Exception('Missing payment reference');
        }

        // Find invoice by invoice_number (used as reference)
        $invoice = Invoice::where('invoice_number', $reference)->first();

        if (!$invoice) {
            throw new \Exception("Invoice not found: {$reference}");
        }

        if ($invoice->status === 'paid') {
            return; // Already processed, idempotent
        }

        $this->invoiceService->markAsPaid($invoice, 'payoneer', $transactionId);
    }

    protected function handlePaymentFailed(array $payload): void
    {
        $reference = $payload['reference'] ?? null;

        if (!$reference) {
            return;
        }

        // Update payment link status if exists
        $paymentLink = PaymentLink::whereHas('invoice', function ($q) use ($reference) {
            $q->where('invoice_number', $reference);
        })->where('provider', 'payoneer')
          ->whereIn('status', ['created', 'sent'])
          ->first();

        if ($paymentLink) {
            $paymentLink->update(['status' => 'expired']);
        }
    }
}
