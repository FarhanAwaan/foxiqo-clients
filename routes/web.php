<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SignupController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DealBillingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AgentController as AdminAgentController;
use App\Http\Controllers\Admin\CallLogController as AdminCallLogController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\PaymentReceiptController;
use App\Http\Controllers\Admin\CalendarConnectionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Closer\CompanyController as CloserCompanyController;
use App\Http\Controllers\Closer\AgentController as CloserAgentController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\Billing\DealCheckoutController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\AgentController as CustomerAgentController;
use App\Http\Controllers\Customer\InvoiceController as CustomerInvoiceController;
use App\Http\Controllers\Customer\SubscriptionController as CustomerSubscriptionController;
use App\Http\Controllers\CallLogController;
use App\Http\Controllers\Customer\CallLogController as CustomerCallLogController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root Route
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(match (true) {
            auth()->user()->isAdmin() => 'admin.dashboard',
            auth()->user()->isCloser() => 'deals.index',
            default => 'customer.dashboard',
        });
    }
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::get('signup/{token}', [SignupController::class, 'showForm'])->name('signup.form');
    Route::post('signup/{token}', [SignupController::class, 'complete'])->name('signup.complete');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'showForm'])->name('password.reset.show');
    Route::post('reset-password/{token}', [PasswordResetController::class, 'complete'])->name('password.reset.complete');
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Public Billing Routes (No Auth Required)
|--------------------------------------------------------------------------
*/
Route::prefix('billing/pay')->name('billing.payment.')->group(function () {
    Route::get('{token}', [PaymentController::class, 'show'])->name('show');
    Route::post('{token}', [PaymentController::class, 'process'])->name('process');
    Route::get('{token}/bank-details', [PaymentController::class, 'bankDetails'])->name('bank-details');
    Route::post('{token}/upload-receipt', [PaymentController::class, 'uploadReceipt'])->name('upload-receipt');
    Route::get('{token}/receipt-uploaded', [PaymentController::class, 'receiptUploaded'])->name('receipt-uploaded');
    Route::get('{token}/success', [PaymentController::class, 'success'])->name('success');
});

// Portal-hosted Paddle checkout — the customer never touches foxiqo.com for this.
Route::get('billing/deal/{deal:uuid}', [DealCheckoutController::class, 'show'])->name('billing.deal.show');

/*
|--------------------------------------------------------------------------
| Profile Routes (Shared between Admin and Customer)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('index');
    Route::put('/update', [ProfileController::class, 'update'])->name('update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
});

// Shared call details JSON endpoint (used by admin and customer agent show pages)
Route::middleware('auth')->group(function () {
    Route::get('calls/{callLog}', [CallLogController::class, 'show'])->name('calls.details');
});

/*
|--------------------------------------------------------------------------
| Deals (shared: admin sees all, closer sees only their own — enforced
| inline in DealController, gated here by Spatie's role_or_permission)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role_or_permission:admin|closer'])->prefix('deals')->name('deals.')->group(function () {
    Route::get('/', [DealController::class, 'index'])->name('index');
    Route::get('create', [DealController::class, 'create'])->name('create');
    Route::post('/', [DealController::class, 'store'])->name('store');
    Route::get('{deal:uuid}', [DealController::class, 'show'])->name('show');
    Route::post('{deal:uuid}/email-link', [DealController::class, 'emailLink'])->middleware('throttle:6,1')->name('email-link');
    Route::post('{deal:uuid}/void', [DealController::class, 'void'])->name('void');
});

/*
|--------------------------------------------------------------------------
| Closer/manager scoped Customer & Assistant viewer — read-only, limited to
| whatever an admin has explicitly granted (Admin > Users > a closer's
| "Customer & Assistant Access"). Deliberately NOT the admin company/agent
| screens: no invoices, no cost/margin data — safe to have on screen during
| a client call. See App\Http\Controllers\Closer\* for the scoping rules.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role_or_permission:admin|closer'])->prefix('closer')->name('closer.')->group(function () {
    Route::get('customers', [CloserCompanyController::class, 'index'])->name('companies.index');
    Route::get('customers/{company}', [CloserCompanyController::class, 'show'])->name('companies.show');
    Route::get('agents/{agent}', [CloserAgentController::class, 'show'])->name('agents.show');
    Route::get('agents/{agent}/charts/call-volume', [CloserAgentController::class, 'chartCallVolume'])->name('agents.charts.call-volume');
    Route::get('agents/{agent}/charts/sentiment', [CloserAgentController::class, 'chartSentiment'])->name('agents.charts.sentiment');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Company Management
    Route::resource('companies', CompanyController::class);
    Route::post('companies/{company}/regenerate-webhook', [CompanyController::class, 'regenerateWebhook'])->name('companies.regenerate-webhook');

    // User Management
    Route::resource('users', UserController::class);
    Route::post('users/{user}/resend-invitation', [UserController::class, 'resendInvitation'])->name('users.resend-invitation');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::put('users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('users.permissions.update');
    Route::put('users/{user}/access', [UserController::class, 'updateAccess'])->name('users.access.update');

    // Agent Management
    Route::resource('agents', AdminAgentController::class);
    Route::get('agents/{agent}/calls', [AdminCallLogController::class, 'index'])->name('agents.calls.index');
    Route::get('agents/{agent}/charts/call-volume', [AdminAgentController::class, 'chartCallVolume'])->name('agents.charts.call-volume');
    Route::get('agents/{agent}/charts/sentiment', [AdminAgentController::class, 'chartSentiment'])->name('agents.charts.sentiment');

    // Calendar Connections (per-agent)
    Route::get('calendar/google/callback', [CalendarConnectionController::class, 'googleCallback'])->name('calendar.google.callback');
    Route::get('agents/{agent}/calendar/connect-google', [CalendarConnectionController::class, 'connectGoogle'])->name('agents.calendar.connect-google');
    Route::post('agents/{agent}/calendar/connect-cal-com', [CalendarConnectionController::class, 'connectCalCom'])->name('agents.calendar.connect-cal-com');
    Route::delete('agents/{agent}/calendar', [CalendarConnectionController::class, 'disconnect'])->name('agents.calendar.disconnect');

    // Plan Management
    Route::resource('plans', PlanController::class);

    // Subscription Management
    Route::resource('subscriptions', SubscriptionController::class);
    Route::post('subscriptions/{subscription}/activate', [SubscriptionController::class, 'activate'])->name('subscriptions.activate');
    Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    // Deal billing (Paddle) — steer a deal's trial/subscription from the portal. Each action
    // calls Paddle and re-syncs; the customer and admin emails come from the detected change.
    Route::post('deals/{deal:uuid}/billing/extend', [DealBillingController::class, 'extend'])->name('deals.billing.extend');
    Route::post('deals/{deal:uuid}/billing/activate', [DealBillingController::class, 'activate'])->name('deals.billing.activate');
    Route::post('deals/{deal:uuid}/billing/cancel', [DealBillingController::class, 'cancel'])->name('deals.billing.cancel');
    Route::post('deals/{deal:uuid}/billing/resume', [DealBillingController::class, 'resume'])->name('deals.billing.resume');
    Route::post('deals/{deal:uuid}/billing/sync', [DealBillingController::class, 'sync'])->name('deals.billing.sync');

    // Invoice Management
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);
    Route::post('invoices/{invoice}/send-payment-link', [InvoiceController::class, 'sendPaymentLink'])->name('invoices.send-payment-link');
    Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
    Route::get('invoices/{invoice}/paddle-invoice', [InvoiceController::class, 'paddleInvoice'])->name('invoices.paddle-invoice');

    // Payment Receipt Management
    Route::get('receipts', [PaymentReceiptController::class, 'index'])->name('receipts.index');
    Route::get('receipts/{receipt}', [PaymentReceiptController::class, 'show'])->name('receipts.show');
    Route::post('receipts/{receipt}/approve', [PaymentReceiptController::class, 'approve'])->name('receipts.approve');
    Route::post('receipts/{receipt}/reject', [PaymentReceiptController::class, 'reject'])->name('receipts.reject');
    Route::get('receipts/{receipt}/preview', [PaymentReceiptController::class, 'preview'])->name('receipts.preview');
    Route::get('receipts/{receipt}/download', [PaymentReceiptController::class, 'download'])->name('receipts.download');

    // Billing & Usage Overview (finance-focused customer list — MRR, usage,
    // margin, overdue — folds in what used to be the separate Revenue report)
    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');

    // System Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/reveal/{key}', [SettingsController::class, 'reveal'])->middleware('throttle:20,1')->name('settings.reveal');

    // Audit Logs
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Outbound Emails
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::get('notifications/{notification}/body', [NotificationController::class, 'body'])->name('notifications.body');

    // Roles & Permissions (closer's admin-configurable access)
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::post('permissions', [RoleController::class, 'storePermission'])->name('permissions.store');
});

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/
Route::prefix('customer')->middleware(['auth', 'customer'])->name('customer.')->group(function () {
    Route::get('/', [CustomerDashboardController::class, 'index'])->name('dashboard');

    // Agents
    Route::get('agents', [CustomerAgentController::class, 'index'])->name('agents.index');
    Route::get('agents/{agent}', [CustomerAgentController::class, 'show'])->name('agents.show');
    Route::get('agents/{agent}/charts/call-volume', [CustomerAgentController::class, 'chartCallVolume'])->name('agents.charts.call-volume');
    Route::get('agents/{agent}/charts/sentiment', [CustomerAgentController::class, 'chartSentiment'])->name('agents.charts.sentiment');

    // Call Logs
    Route::get('agents/{agent}/calls', [CustomerCallLogController::class, 'index'])->name('calls.index');
    Route::get('calls/{callLog}', [CustomerCallLogController::class, 'show'])->name('calls.show');

    // Billing - Invoices
    Route::get('invoices', [CustomerInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [CustomerInvoiceController::class, 'show'])->name('invoices.show');

    // Billing - Subscriptions
    Route::get('subscriptions', [CustomerSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('subscriptions/{subscription}', [CustomerSubscriptionController::class, 'show'])->name('subscriptions.show');
});
