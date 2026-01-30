<?php

namespace App\Events;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionActivated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public Invoice $invoice
    ) {}
}
