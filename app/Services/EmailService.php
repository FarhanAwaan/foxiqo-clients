<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Mail\AdminAlertMail;
use App\Mail\DealBillingUpdateMail;
use App\Mail\DealCheckoutLinkMail;
use App\Mail\DealPaidMail;
use App\Mail\DealTrialEndingMail;
use App\Mail\MissedCallAlertMail;
use App\Mail\NewReceiptUploadedMail;
use App\Mail\PaddleChargeReceiptMail;
use App\Mail\PaddleUsageChargeReceiptMail;
use App\Mail\PasswordResetMail;
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
use App\Models\CallLog;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\PaymentReceipt;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\BillingTime;
use App\Support\DealTerms;
use Carbon\Carbon;
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

    /**
     * "Your card was charged" for a Paddle retainer charge — a monthly renewal, or the first charge
     * when a free trial ends. The paid-wording counterpart of sendSubscriptionRenewal(), which is for
     * invoices this app collects itself and asks for payment. Goes to the company's billing contact
     * when an assistant is attached, otherwise to the deal's own address (no assistant yet).
     */
    public function sendPaddleChargeReceipt(Invoice $invoice, ?Subscription $subscription, ?Deal $deal, bool $convertedFromTrial, ?Invoice $usageInvoice = null): void
    {
        $invoice->load('company');
        $company = $invoice->company;

        $name = $subscription?->agent?->name ?? $deal?->business_name ?? $company->name;
        $recipient = $subscription ? $company->effective_billing_email : ($deal?->email ?? $company->effective_billing_email);
        $greeting = $subscription || !$deal ? "Dear {$company->name}" : 'Hi ' . DealTerms::firstName($deal);

        // The mirror is refreshed right after a charge is recorded, but if that read failed it still holds the
        // date that just passed — better to leave "next charge" out than to print a stale one.
        $next = $deal?->next_billed_at?->isFuture() ? $deal->next_billed_at : null;

        $mailable = new PaddleChargeReceiptMail($invoice, $name, $greeting, $convertedFromTrial, $next, $usageInvoice);
        $totalAmount = (float) $invoice->amount + (float) ($usageInvoice->amount ?? 0);

        $this->createNotificationAndDispatch(
            mailable: $mailable,
            recipientEmail: $recipient,
            type: 'paddle_charge_receipt',
            subject: $mailable->envelope()->subject,
            body: ($convertedFromTrial ? 'First charge after the free trial' : 'Monthly charge') . " of " . DealTerms::money($totalAmount) . " for {$name}"
                . ($usageInvoice ? " (retainer + usage, combined)" : '') . " confirmed to {$recipient} (invoice {$invoice->invoice_number}" . ($usageInvoice ? ", {$usageInvoice->invoice_number}" : '') . ").",
            companyId: $company->id,
            data: array_filter([
                'invoice_id' => $invoice->id,
                'usage_invoice_id' => $usageInvoice?->id,
                'subscription_id' => $subscription?->id,
                'deal_id' => $deal?->id,
            ])
        );
    }

    /**
     * "We charged you" for a usage-overage one-time charge Paddle collected on top of the retainer —
     * PaddleChargeReceiptMail's sibling for the metered-minutes amount, which is always its own
     * charge (see PaddleLifecycleService::chargeUsageInvoice()), never combined into one number.
     */
    public function sendPaddleUsageChargeReceipt(Invoice $invoice, Subscription $subscription): void
    {
        $invoice->load('company');
        $company = $invoice->company;
        $subscription->loadMissing(['agent', 'plan']);

        $mailable = new PaddleUsageChargeReceiptMail($invoice, $subscription);

        $this->createNotificationAndDispatch(
            mailable: $mailable,
            recipientEmail: $company->effective_billing_email,
            type: 'paddle_usage_charge_receipt',
            subject: $mailable->envelope()->subject,
            body: "Usage charge of " . DealTerms::money($invoice->amount) . " ({$invoice->usage_minutes} min) confirmed to {$company->effective_billing_email} for {$company->name} (invoice {$invoice->invoice_number}).",
            companyId: $company->id,
            data: [
                'invoice_id' => $invoice->id,
                'subscription_id' => $subscription->id,
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

    public function sendPasswordReset(User $user, string $token): void
    {
        $this->createNotificationAndDispatch(
            mailable: new PasswordResetMail($user, $token),
            recipientEmail: $user->email,
            type: 'password_reset',
            subject: 'Reset Your Password — ' . config('app.name', 'Foxiqo'),
            body: "Password reset link sent to {$user->email}.",
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

    public function sendMissedCallAlert(CallLog $callLog): void
    {
        $callLog->load('agent.company');
        $agent = $callLog->agent;
        $company = $agent->company;

        $this->createNotificationAndDispatch(
            mailable: new MissedCallAlertMail($callLog),
            recipientEmail: $agent->missed_call_alert_recipient,
            type: 'missed_call_alert',
            subject: "Missed Call: {$agent->name}",
            body: "{$agent->name} missed a call from {$callLog->from_number}.",
            companyId: $company->id,
            data: [
                'agent_id' => $agent->id,
                'call_log_id' => $callLog->id,
            ]
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
    // Deal emails — customer-facing (Paddle deals; the recipient is the deal's own
    // email, since no Company/billing email exists yet before payment)
    // ──────────────────────────────────────────────

    /** The secure checkout link + a plain-language summary of what they're agreeing to. */
    public function sendDealCheckoutLink(Deal $deal): void
    {
        $mailable = new DealCheckoutLinkMail($deal);

        $this->createNotificationAndDispatch(
            mailable: $mailable,
            recipientEmail: $deal->email,
            type: 'deal_checkout_link',
            subject: $mailable->envelope()->subject,
            body: "Checkout link for {$deal->business_name} sent to {$deal->email} — " . DealTerms::summary($deal) . '.',
            companyId: $deal->company_id,
            data: ['deal_id' => $deal->id]
        );

        $deal->forceFill(['link_emailed_at' => now()])->save();
    }

    /** "Payment received — here's what happens next", with Paddle's confirmed first-charge date. */
    public function sendDealPaid(Deal $deal, bool $invited = true): void
    {
        $mailable = new DealPaidMail($deal, $invited);

        $this->createNotificationAndDispatch(
            mailable: $mailable,
            recipientEmail: $deal->email,
            type: 'deal_paid',
            subject: $mailable->envelope()->subject,
            body: "Payment confirmation and next steps sent to {$deal->email} for {$deal->business_name}.",
            companyId: $deal->company_id,
            data: ['deal_id' => $deal->id]
        );
    }

    /** Reminder that a Paddle trial's first charge is coming (see paddle:send-trial-reminders). */
    public function sendDealTrialEnding(Deal $deal): void
    {
        $mailable = new DealTrialEndingMail($deal);

        $this->createNotificationAndDispatch(
            mailable: $mailable,
            recipientEmail: $deal->email,
            type: 'deal_trial_ending',
            subject: $mailable->envelope()->subject,
            body: "Trial ending reminder for {$deal->business_name}: first charge " . BillingTime::date($deal->nextChargeAt()) . '.',
            companyId: $deal->company_id,
            data: ['deal_id' => $deal->id, 'charge_at' => $deal->nextChargeAt()?->toIso8601String()]
        );
    }

    /**
     * A change to a deal's billing the customer must be told about.
     *
     * @param string $kind one of DealBillingUpdateMail::KINDS
     */
    public function sendDealBillingUpdate(Deal $deal, string $kind, ?Carbon $previousChargeAt = null): void
    {
        $mailable = new DealBillingUpdateMail($deal, $kind, $previousChargeAt);

        $this->createNotificationAndDispatch(
            mailable: $mailable,
            recipientEmail: $deal->email,
            type: "deal_{$kind}",
            subject: $mailable->envelope()->subject,
            body: "Billing update ({$kind}) sent to {$deal->email} for {$deal->business_name}.",
            companyId: $deal->company_id,
            data: ['deal_id' => $deal->id]
        );
    }

    // ──────────────────────────────────────────────
    // Admin Notification Emails
    // ──────────────────────────────────────────────

    public function sendNewReceiptUploaded(PaymentReceipt $receipt): void
    {
        $receipt->load(['invoice.company']);
        $company = $receipt->invoice->company;

        foreach ($this->adminRecipients() as $adminEmail) {
            $this->createNotificationAndDispatch(
                mailable: new NewReceiptUploadedMail($receipt),
                recipientEmail: $adminEmail,
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
    }

    public function sendUsageAlert(Subscription $subscription): void
    {
        $subscription->load(['company', 'agent', 'plan']);

        foreach ($this->adminRecipients() as $adminEmail) {
            $this->createNotificationAndDispatch(
                mailable: new UsageAlertMail($subscription),
                recipientEmail: $adminEmail,
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
    }

    /**
     * The one admin-facing "something happened, here's the next step" email (see
     * AdminAlertMail). Goes to every admin recipient — plus $alsoNotify, e.g. the closer
     * who owns the deal — one Notification row each, so Admin → Emails shows exactly who
     * was told what.
     *
     * @param array<string, string> $facts label => value
     * @param string $tone info | success | warning | danger
     * @param array<int, string> $alsoNotify extra recipient emails
     */
    public function notifyAdmins(
        string $type,
        string $subject,
        string $headline,
        string $intro,
        array $facts = [],
        ?string $ctaUrl = null,
        ?string $ctaLabel = null,
        string $tone = 'info',
        ?Deal $deal = null,
        array $alsoNotify = []
    ): void {
        foreach ($this->adminRecipients($alsoNotify) as $email) {
            $this->createNotificationAndDispatch(
                mailable: new AdminAlertMail($subject, $headline, $intro, $facts, $ctaUrl, $ctaLabel, $tone),
                recipientEmail: $email,
                type: $type,
                subject: $subject,
                body: $intro,
                companyId: $deal?->company_id,
                data: $deal ? ['deal_id' => $deal->id] : []
            );
        }
    }

    /**
     * Who counts as "the admin" for notifications: the addresses configured in Settings
     * (comma-separated), otherwise every active admin user, and only as a last resort the
     * mail from-address. (Before this, admin emails silently went to MAIL_FROM_ADDRESS —
     * fine while that happened to be the admin's inbox, a black hole otherwise.)
     *
     * @param array<int, string> $extra additional recipients merged in (deduplicated)
     * @return array<int, string>
     */
    public function adminRecipients(array $extra = []): array
    {
        $emails = collect(preg_split('/[\s,;]+/', (string) SystemSetting::getValue('admin_notification_email', ''), -1, PREG_SPLIT_NO_EMPTY));

        if ($emails->isEmpty()) {
            $emails = User::where('role', 'admin')->where('status', 'active')->pluck('email');
        }

        if ($emails->isEmpty() && config('mail.from.address')) {
            $emails = collect([config('mail.from.address')]);
        }

        return $emails->merge($extra)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
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
}
