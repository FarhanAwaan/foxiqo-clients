<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SignupController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AgentController as AdminAgentController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\AgentController as CustomerAgentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root Route
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('customer.dashboard');
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
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

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

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Company Management
    Route::resource('companies', CompanyController::class);

    // User Management
    Route::resource('users', UserController::class);
    Route::post('users/{user}/resend-invitation', [UserController::class, 'resendInvitation'])->name('users.resend-invitation');

    // Agent Management
    Route::resource('agents', AdminAgentController::class);
    Route::get('agents/{agent}/calls/{callLog}', [AdminAgentController::class, 'callDetails'])->name('agents.calls.details');

    // Plan Management
    Route::resource('plans', PlanController::class);

    // Subscription Management
    Route::resource('subscriptions', SubscriptionController::class);
    Route::post('subscriptions/{subscription}/activate', [SubscriptionController::class, 'activate'])->name('subscriptions.activate');
    Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    // Invoice Management
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);
    Route::post('invoices/{invoice}/send-payment-link', [InvoiceController::class, 'sendPaymentLink'])->name('invoices.send-payment-link');
    Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');

    // Revenue Reports
    Route::get('revenue', [RevenueController::class, 'index'])->name('revenue.index');

    // System Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
});

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/
Route::prefix('customer')->middleware(['auth', 'customer'])->name('customer.')->group(function () {
    Route::get('/', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::get('agents', [CustomerAgentController::class, 'index'])->name('agents.index');
    Route::get('agents/{agent}', [CustomerAgentController::class, 'show'])->name('agents.show');
    Route::get('agents/{agent}/calls/{callLog}', [CustomerAgentController::class, 'callDetails'])->name('agents.calls.details');
});
