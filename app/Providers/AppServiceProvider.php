<?php

namespace App\Providers;

use App\Events\CircuitBreakerTriggered;
use App\Events\PaymentReceived;
use App\Events\SubscriptionActivated;
use App\Listeners\SendPaymentConfirmationEmail;
use App\Listeners\SendSubscriptionActivatedEmail;
use App\Listeners\SendUsageAlertEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(SubscriptionActivated::class, SendSubscriptionActivatedEmail::class);
        Event::listen(PaymentReceived::class, SendPaymentConfirmationEmail::class);
        Event::listen(CircuitBreakerTriggered::class, SendUsageAlertEmail::class);
    }
}
