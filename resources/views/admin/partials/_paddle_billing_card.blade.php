{{--
    Billing & trial controls for a paid Deal. Expects $deal (with childDeals loaded on setup-only deals).
    Shown on the deal page and on the subscription page of a Paddle-managed assistant. Only admins get
    the action buttons; closers see the same status read-only. All Paddle calls happen in
    PaddleLifecycleService — this is display + forms.
--}}
@php
    $canManage = auth()->user()->isAdmin();
    $status = $deal->paddle_status;
    $chargeAt = $deal->nextChargeAt();
    $daysLeft = \App\Support\BillingTime::daysUntil($chargeAt);
    $monthly = \App\Support\DealTerms::money($deal->agreed_monthly_price);
    $ended = $status === 'canceled';
@endphp

@if($deal->status === 'paid')
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Billing</h3>
            <div class="card-actions d-flex align-items-center gap-2">
                @if($deal->paddle_status_label)
                    <span class="badge {{ $deal->paddle_status_badge }} fs-6">{{ $deal->paddle_status_label }}</span>
                @endif
                @if($canManage && $deal->isPaddleManaged() && !$ended)
                    <form action="{{ route('admin.deals.billing.sync', $deal) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Re-read this subscription from Paddle and record anything missed">Sync with Paddle</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card-body">
            @if($deal->isSetupOnly())
                {{-- Setup-only: nothing recurring exists; the retainer is a separate deal --}}
                @php $retainer = $deal->childDeals->firstWhere('status', 'paid') ?? $deal->childDeals->firstWhere('status', 'sent'); @endphp
                <p class="mb-2">This deal was <strong>setup fee only</strong> — there is no subscription and nothing recurring is billed.</p>

                @if($retainer)
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted">Monthly retainer deal:</span>
                        <a href="{{ route('deals.show', $retainer) }}">{{ \App\Support\DealTerms::money($retainer->agreed_monthly_price) }}/mo</a>
                        <span class="badge {{ $retainer->status === 'paid' ? 'bg-green-lt' : 'bg-blue-lt' }}">{{ $retainer->status === 'paid' ? 'Paid' : 'Awaiting payment' }}</span>
                    </div>
                @else
                    <a href="{{ route('deals.create', ['parent' => $deal->uuid]) }}" class="btn btn-primary">Add monthly retainer</a>
                    <div class="text-muted small mt-2">
                        Once the monthly price is agreed, add it here. The customer gets a separate checkout link for the retainer
                        (a free trial is optional), and once paid it funds the assistant's subscription.
                    </div>
                @endif

            @elseif(!$status)
                <div class="alert alert-warning mb-0">
                    Paddle hasn't reported this subscription's status to the portal yet.
                    @if($canManage && $deal->isPaddleManaged())
                        Use <strong>Sync with Paddle</strong> to load it now.
                    @else
                        It appears automatically within a few minutes.
                    @endif
                </div>

            @else
                <div class="datagrid mb-3">
                    @if($status === 'trialing')
                        <div class="datagrid-item">
                            {{-- With a cancellation scheduled nothing is charged on this date — it's just when the trial ends --}}
                            <div class="datagrid-title">{{ $deal->hasScheduledCancellation() ? 'Trial ends (no charge)' : 'First charge' }}</div>
                            <div class="datagrid-content">
                                {{ \App\Support\BillingTime::dateTime($chargeAt) }}
                                @if($daysLeft !== null)
                                    <span class="badge bg-purple-lt ms-1">{{ $daysLeft <= 0 ? 'today' : ($daysLeft === 1 ? 'tomorrow' : "in {$daysLeft} days") }}</span>
                                @endif
                                @if(\App\Support\BillingTime::isWeekend($chargeAt))
                                    <span class="badge bg-yellow-lt ms-1" title="This charge lands on a weekend — move it if nobody will be around">Weekend</span>
                                @endif
                            </div>
                        </div>
                    @elseif(!$ended && $deal->next_billed_at)
                        <div class="datagrid-item">
                            <div class="datagrid-title">Next charge</div>
                            <div class="datagrid-content">{{ \App\Support\BillingTime::dateTime($deal->next_billed_at) }}</div>
                        </div>
                    @endif
                    <div class="datagrid-item">
                        <div class="datagrid-title">Monthly retainer</div>
                        <div class="datagrid-content text-money">{{ $monthly }}</div>
                    </div>
                    @if($deal->is_trial)
                        <div class="datagrid-item">
                            <div class="datagrid-title">Trial</div>
                            <div class="datagrid-content">{{ $deal->trial_days }} days from checkout</div>
                        </div>
                    @endif
                    @if($ended && $deal->paddle_canceled_at)
                        <div class="datagrid-item">
                            <div class="datagrid-title">Cancelled</div>
                            <div class="datagrid-content">{{ \App\Support\BillingTime::dateTime($deal->paddle_canceled_at) }}</div>
                        </div>
                    @endif
                </div>

                @if($deal->hasScheduledCancellation() && !$ended)
                    <div class="alert alert-warning d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <strong>Set to cancel on {{ \App\Support\BillingTime::date($deal->paddle_scheduled_change_at) }}.</strong>
                            Nothing further will be charged after that.
                        </div>
                        @if($canManage)
                            <form action="{{ route('admin.deals.billing.resume', $deal) }}" method="POST" class="ms-3">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning">Keep subscription</button>
                            </form>
                        @endif
                    </div>
                @endif

                @if($deal->isChargeOverdue() && $status !== 'past_due')
                    <div class="alert alert-danger mb-3">
                        <strong>The {{ \App\Support\BillingTime::date($deal->next_billed_at) }} charge hasn't happened.</strong>
                        Paddle's charge date passed more than {{ \App\Models\Deal::chargeOverdueGraceHours() }} hours ago and nothing has been charged or recorded,
                        yet the subscription still shows as {{ strtolower($deal->paddle_status_label ?? $status) }}. Nothing has been paused and the customer hasn't been contacted.
                        Check it in Paddle (the card on file, a billing hold, an incident)@if($canManage), then use <strong>Sync with Paddle</strong>@endif.
                    </div>
                @endif

                @if($status === 'past_due')
                    <div class="alert alert-danger mb-3">
                        Paddle couldn't collect the last charge. It retries automatically and emails the customer; you may want to reach out.
                    </div>
                @endif

                @if($canManage && !$ended)
                    <div class="btn-list">
                        @if($status === 'trialing')
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#dealExtendModal">Change first-charge date</button>
                            <form action="{{ route('admin.deals.billing.activate', $deal) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm({{ \Illuminate\Support\Js::from('Charge ' . $monthly . ' to the card on file right now and end the trial early?') }})">
                                @csrf
                                <button type="submit" class="btn btn-outline-success">Start billing now</button>
                            </form>
                        @endif
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#dealCancelModal">Cancel subscription…</button>
                    </div>
                    @if($status === 'trialing')
                        <div class="text-muted small mt-2">
                            Client wants a few more days, or it lands on a weekend? <strong>Change first-charge date</strong>.
                            Assistant went live early? <strong>Start billing now</strong>. Not continuing? <strong>Cancel</strong> before the date and nothing is charged.
                            The customer is emailed about every change.
                        </div>
                    @endif
                @endif
            @endif
        </div>
    </div>

    @if($canManage && $deal->isPaddleManaged() && !$ended && $status)
        {{-- Change first-charge date --}}
        @if($status === 'trialing')
            <div class="modal modal-blur fade" id="dealExtendModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ route('admin.deals.billing.extend', $deal) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Change first-charge date</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">
                                    Currently <strong>{{ \App\Support\BillingTime::dateTime($chargeAt) }}</strong>.
                                    Paddle charges {{ $monthly }} to the card on file at the new time, and the customer is emailed the new date.
                                </p>
                                <div class="mb-3">
                                    <label class="form-label">Add days</label>
                                    <div class="btn-group w-100" role="group">
                                        @foreach([1, 2, 3, 7, 14] as $d)
                                            <input type="radio" class="btn-check" name="days" id="extendDays{{ $d }}" value="{{ $d }}" autocomplete="off">
                                            <label class="btn btn-outline-primary" for="extendDays{{ $d }}">+{{ $d }}</label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="extendDate">…or move to a specific date</label>
                                    <input type="date" name="date" id="extendDate" class="form-control"
                                           min="{{ now(\App\Support\BillingTime::timezone())->addDay()->format('Y-m-d') }}">
                                    <div class="form-hint">Same time of day as now. If you pick a date it overrides the days above. Times are {{ \App\Support\BillingTime::timezone() }}.</div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label" for="extendReason">Note (optional)</label>
                                    <input type="text" name="reason" id="extendReason" class="form-control" maxlength="500" placeholder="e.g. Client asked for two more days">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary ms-auto">Update date</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- Cancel --}}
        <div class="modal modal-blur fade" id="dealCancelModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('admin.deals.billing.cancel', $deal) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Cancel subscription</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>How should <strong>{{ $deal->business_name }}</strong>'s subscription end?</p>
                            <label class="form-check mb-2">
                                <input type="radio" name="mode" value="scheduled" class="form-check-input" {{ $deal->hasScheduledCancellation() ? 'disabled' : 'checked' }}>
                                <span class="form-check-label">
                                    <strong>At the end of the {{ $status === 'trialing' ? 'trial' : 'current period' }}</strong>
                                    <span class="d-block text-muted small">
                                        Nothing further is charged and the assistant keeps running until
                                        {{ \App\Support\BillingTime::date($status === 'trialing' ? $chargeAt : ($deal->paddle_period_end ?? $deal->next_billed_at)) }}.
                                        @if($deal->hasScheduledCancellation()) (Already scheduled.) @endif
                                    </span>
                                </span>
                            </label>
                            <label class="form-check mb-3">
                                <input type="radio" name="mode" value="immediately" class="form-check-input" {{ $deal->hasScheduledCancellation() ? 'checked' : '' }}>
                                <span class="form-check-label">
                                    <strong>Immediately</strong>
                                    <span class="d-block text-muted small">Ends now. If an assistant is attached to this deal it is deactivated.</span>
                                </span>
                            </label>
                            <div class="mb-0">
                                <label class="form-label" for="cancelReason">Reason (optional)</label>
                                <input type="text" name="reason" id="cancelReason" class="form-control" maxlength="500" placeholder="e.g. Client decided not to continue after the trial">
                            </div>
                            <div class="text-muted small mt-3">The customer is emailed either way.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Keep subscription</button>
                            <button type="submit" class="btn btn-danger ms-auto">Cancel subscription</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endif
