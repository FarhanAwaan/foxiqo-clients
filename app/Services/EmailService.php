<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Mail\NewReceiptUploadedMail;
use App\Mail\PaymentConfirmationMail;
use App\Mail\PaymentLinkMail;
use App\Mail\PaymentReminderMail;
use App\Mail\ReceiptApprovedMail;
use App\Mail\ReceiptRejectedMail;
use App\Mail\SubscriptionActivatedMail;
use App\Mail\SubscriptionCancelledMail;
use App\Mail\SubscriptionCreatedMail;
use App\Mail\SubscriptionExpiryWarningMail;
use App\Mail\SubscriptionRenewalMail;
use App\Mail\TrialEndingMail;
use App\Mail\TrialExpiredMail;
use App\Mail\TrialStartedMail;
use App\Mail\UsageAlertMail;
use App\Mail\UserInvitationMail;
use App\Mail\WelcomeMail;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\PaymentReceipt;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;

class EmailService
{
    // ──────────────────────────────────────────────
    // Customer-Facing Emails
    // ──────────────────────────────────────────────

    public function sendPaymentLink(Invoice $invoice, PaymentLink $paymentLink): void
    {
        $invoice->load('company');
        $company = $invoice->company;

        $this->createNotificationAndDispatch(
            mailable: new PaymentLinkMail($invoice, $paymentLink),
            recipientEmail: $company->effective_billing_email,
            type: 'payment_link',
            subject: "Payment Required: Invoice {$invoice->invoice_number}",
            body: "Payment link sent for invoice {$invoice->invoice_number} — Amount: \${$invoice->amount}",
            companyId: $company->id,
            data: [
                'invoice_id' => $invoice->id,
                'payment_link_id' => $paymentLink->id,
            ]
        );
    }

    public function sendPaymentConfirmation(Invoice $invoice, Payment $payment): void
    {
        $invoice->load('company');
        $company = $invoice->company;

        $this->createNotificationAndDispatch(
            mailable: new PaymentConfirmationMail($invoice, $payment),
            recipientEmail: $company->effective_billing_email,
            type: 'payment_confirmation',
            subject: "Payment Confirmed: Invoice {$invoice->invoice_number}",
            body: "Payment of \${$payment->amount} received for invoice {$invoice->invoice_number}",
            companyId: $company->id,
            data: [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
            ]
        );
    }

    public function sendSubscriptionCreated(Subscription $subscription, Invoice $invoice, PaymentLink $paymentLink): void
    {
        $subscription->load(['company', 'agent', 'plan']);
        $company = $subscription->company;

        $this->createNotificationAndDispatch(
            mailable: new SubscriptionCreatedMail($subscription, $invoice, $paymentLink),
            recipientEmail: $company->effective_billing_email,
            type: 'subscription_created',
            subject: "New Subscription — Payment Required: {$subscription->agent->name}",
            body: "A subscription for {$subscription->agent->name} on the {$subscription->plan->name} plan has been created. Payment of \${$invoice->amount} is required.",
            companyId: $company->id,
            data: [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
                'payment_link_id' => $paymentLink->id,
            ]
        );
    }

    public function sendSubscriptionActivated(Subscription $subscription, Invoice $invoice): void
    {
        $subscription->load(['company', 'agent', 'plan']);
        $company = $subscription->company;

        $this->createNotificationAndDispatch(
            mailable: new SubscriptionActivatedMail($subscription, $invoice),
            recipientEmail: $company->effective_billing_email,
            type: 'subscription_activated',
            subject: "Subscription Active: {$subscription->agent->name}",
            body: "Your subscription for {$subscription->agent->name} on the {$subscription->plan->name} plan is now active.",
            companyId: $company->id,
            data: [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
            ]
        );
    }

    public function sendSubscriptionCancelled(Subscription $subscription): void
    {
        $subscription->load(['company', 'agent', 'plan']);
        $company = $subscription->company;

        $this->createNotificationAndDispatch(
            mailable: new SubscriptionCancelledMail($subscription),
            recipientEmail: $company->effective_billing_email,
            type: 'subscription_cancelled',
            subject: "Subscription Cancelled: {$subscription->agent->name}",
            body: "Your subscription for {$subscription->agent->name} has been cancelled.",
            companyId: $company->id,
            data: [
                'subscription_id' => $subscription->id,
            ]
        );
    }

    public function sendSubscriptionRenewal(Subscription $subscription, Invoice $invoice): void
    {
        $subscription->load(['company', 'agent', 'plan']);
        $company = $subscription->company;

        $this->createNotificationAndDispatch(
            mailable: new SubscriptionRenewalMail($subscription, $invoice),
            recipientEmail: $company->effective_billing_email,
            type: 'subscription_renewal',
            subject: "Subscription Renewed: {$subscription->agent->name}",
            body: "Your subscription for {$subscription->agent->name} has been renewed. Invoice {$invoice->invoice_number} created.",
            companyId: $company->id,
            data: [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
            ]
        );
    }

    public function sendReceiptApproved(PaymentReceipt $receipt): void
    {
        $receipt->load(['invoice.company']);
        $company = $receipt->invoice->company;

        $this->createNotificationAndDispatch(
            mailable: new ReceiptApprovedMail($receipt),
            recipientEmail: $company->effective_billing_email,
            type: 'receipt_approved',
            subject: "Receipt Approved: Invoice {$receipt->invoice->invoice_number}",
            body: "Your payment receipt for invoice {$receipt->invoice->invoice_number} has been approved.",
            companyId: $company->id,
            data: [
                'receipt_id' => $receipt->id,
                'invoice_id' => $receipt->invoice_id,
            ]
        );
    }

    public function sendReceiptRejected(PaymentReceipt $receipt): void
    {
        $receipt->load(['invoice.company', 'paymentLink']);
        $company = $receipt->invoice->company;

        $this->createNotificationAndDispatch(
            mailable: new ReceiptRejectedMail($receipt),
            recipientEmail: $company->effective_billing_email,
            type: 'receipt_rejected',
            subject: "Receipt Rejected: Invoice {$receipt->invoice->invoice_number}",
            body: "Your payment receipt for invoice {$receipt->invoice->invoice_number} was rejected. Reason: {$receipt->rejection_reason}",
            companyId: $company->id,
            data: [
                'receipt_id' => $receipt->id,
                'invoice_id' => $receipt->invoice_id,
            ]
        );
    }

    public function sendSubscriptionExpiryWarning(Subscription $subscription): void
    {
        $subscription->load(['company', 'agent', 'plan']);
        $company = $subscription->company;

        $this->createNotificationAndDispatch(
            mailable: new SubscriptionExpiryWarningMail($subscription),
            recipientEmail: $company->effective_billing_email,
            type: 'subscription_expiry_warning',
            subject: "Subscription Expiring Soon: {$subscription->agent->name}",
            body: "Your subscription for {$subscription->agent->name} expires on {$subscription->current_period_end->format('M d, Y')}.",
            companyId: $company->id,
            data: [
                'subscription_id' => $subscription->id,
            ]
        );
    }

    public function sendPaymentReminder(Invoice $invoice): void
    {
        $invoice->load('company');
        $company = $invoice->company;

        $this->createNotificationAndDispatch(
            mailable: new PaymentReminderMail($invoice),
            recipientEmail: $company->effective_billing_email,
            type: 'payment_reminder',
            subject: "Payment Reminder: Invoice {$invoice->invoice_number}",
            body: "Reminder: Invoice {$invoice->invoice_number} for \${$invoice->amount} is due.",
            companyId: $company->id,
            data: [
                'invoice_id' => $invoice->id,
            ]
        );
    }

    public function sendUserInvitation(User $user, string $token): void
    {
        $this->createNotificationAndDispatch(
            mailable: new UserInvitationMail($user, $token),
            recipientEmail: $user->email,
            type: 'user_invitation',
            subject: "You've been invited to " . config('app.name', 'Foxiqo'),
            body: "Invitation sent to {$user->email} to join " . config('app.name', 'Foxiqo') . ".",
            companyId: $user->company_id,
            userId: $user->id,
            data: [
                'user_id' => $user->id,
            ]
        );
    }

    public function sendWelcomeEmail(User $user): void
    {
        $user->load('company');

        $this->createNotificationAndDispatch(
            mailable: new WelcomeMail($user),
            recipientEmail: $user->email,
            type: 'welcome',
            subject: "Welcome to " . config('app.name', 'Foxiqo') . "!",
            body: "Welcome email sent to {$user->full_name}.",
            companyId: $user->company_id,
            userId: $user->id,
            data: [
                'user_id' => $user->id,
            ]
        );
    }

    public function sendTrialStarted(Subscription $subscription): void
    {
        $subscription->load(['company', 'agent', 'plan']);
        $company = $subscription->company;

        $this->createNotificationAndDispatch(
            mailable: new TrialStartedMail($subscription),
            recipientEmail: $company->effective_billing_email,
            type: 'trial_started',
            subject: "Your Free Trial Has Started: {$subscription->agent->name}",
            body: "Your {$subscription->trial_days}-day free trial for {$subscription->agent->name} on the {$subscription->plan->name} plan has started. Trial ends {$subscription->trial_ends_at->format('M d, Y')}.",
            companyId: $company->id,
            data: ['subscription_id' => $subscription->id]
        );
    }

    public function sendTrialEndingWarning(Subscription $subscription): void
    {
        $subscription->load(['company', 'agent', 'plan']);
        $company = $subscription->company;
        $daysLeft = $subscription->trialDaysRemaining();

        $this->createNotificationAndDispatch(
            mailable: new TrialEndingMail($subscription),
            recipientEmail: $company->effective_billing_email,
            type: 'trial_ending',
            subject: "Your Trial Ends in {$daysLeft} Day(s): {$subscription->agent->name}",
            body: "Your free trial for {$subscription->agent->name} ends on {$subscription->trial_ends_at->format('M d, Y')}. After that, a subscription invoice will be sent.",
            companyId: $company->id,
            data: ['subscription_id' => $subscription->id]
        );
    }

    public function sendTrialExpired(Subscription $subscription, Invoice $invoice, PaymentLink $paymentLink): void
    {
        $subscription->load(['company', 'agent', 'plan']);
        $company = $subscription->company;

        $this->createNotificationAndDispatch(
            mailable: new TrialExpiredMail($subscription, $invoice, $paymentLink),
            recipientEmail: $company->effective_billing_email,
            type: 'trial_expired',
            subject: "Your Trial Has Ended — Payment Required: {$subscription->agent->name}",
            body: "Your free trial for {$subscription->agent->name} has ended. Invoice {$invoice->invoice_number} for \${$invoice->amount} has been created.",
            companyId: $company->id,
            data: [
                'subscription_id' => $subscription->id,
                'invoice_id'      => $invoice->id,
                'payment_link_id' => $paymentLink->id,
            ]
        );
    }

    // ──────────────────────────────────────────────
    // Admin Notification Emails
    // ──────────────────────────────────────────────

    public function sendNewReceiptUploaded(PaymentReceipt $receipt): void
    {
        $receipt->load(['invoice.company']);
        $company = $receipt->invoice->company;

        $this->createNotificationAndDispatch(
            mailable: new NewReceiptUploadedMail($receipt),
            recipientEmail: $this->getAdminEmail(),
            type: 'new_receipt_uploaded',
            subject: "New Receipt: {$company->name} — Invoice {$receipt->invoice->invoice_number}",
            body: "{$company->name} uploaded a payment receipt for invoice {$receipt->invoice->invoice_number}.",
            companyId: $company->id,
            data: [
                'receipt_id' => $receipt->id,
                'invoice_id' => $receipt->invoice_id,
            ]
        );
    }

    public function sendUsageAlert(Subscription $subscription): void
    {
        $subscription->load(['company', 'agent', 'plan']);

        $this->createNotificationAndDispatch(
            mailable: new UsageAlertMail($subscription),
            recipientEmail: $this->getAdminEmail(),
            type: 'usage_alert',
            subject: "Usage Alert: {$subscription->agent->name} — {$subscription->company->name}",
            body: "Agent {$subscription->agent->name} has triggered the circuit breaker ({$subscription->minutes_used} minutes used).",
            companyId: $subscription->company_id,
            data: [
                'subscription_id' => $subscription->id,
                'agent_id' => $subscription->agent_id,
            ]
        );
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    protected function createNotificationAndDispatch(
        Mailable $mailable,
        string $recipientEmail,
        string $type,
        string $subject,
        string $body,
        ?int $companyId = null,
        ?int $userId = null,
        array $data = []
    ): void {
        $notification = Notification::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'type' => $type,
            'channel' => 'email',
            'subject' => $subject,
            'body' => $body,
            'data' => array_merge($data, ['recipient_email' => $recipientEmail]),
        ]);

        SendEmailJob::dispatch($mailable, $recipientEmail, $notification->id);
    }

    protected function getAdminEmail(): string
    {
        return SystemSetting::getValue(
            'admin_notification_email',
            config('mail.from.address')
        );
    }
}
