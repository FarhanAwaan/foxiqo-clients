@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.closer')

@section('title', $deal->business_name)

@section('page-pretitle')
    {{ $deal->isRetainerDeal() ? 'Monthly retainer deal' : 'Deal' }}
@endsection

@section('page-header')
    {{ $deal->business_name }}
    @if($deal->company?->is_demo)
        <span class="badge bg-purple-lt ms-2" title="Excluded from automated billing — no renewal, usage, overdue or reconcile processing, no billing emails">Demo</span>
    @endif
@endsection

@section('content')
    @php $isAdmin = auth()->user()->isAdmin(); @endphp

    <div class="row">
        <div class="col-lg-8">
            @if($deal->status === 'sent')
                <div class="card card-md bg-primary-lt mb-4">
                    <div class="card-body p-4">
                        <h3 class="mb-1">Checkout Link</h3>
                        <p class="text-muted small mb-2">Send this to {{ $deal->customer_name }} to complete payment — or email it to them from here.</p>
                        @php $checkoutUrl = route('billing.deal.show', $deal); @endphp
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="checkoutUrl" value="{{ $checkoutUrl }}" readonly>
                            <button class="btn btn-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('checkoutUrl').value)">
                                Copy Link
                            </button>
                            <a href="{{ $checkoutUrl }}" target="_blank" class="btn btn-outline-primary">Open</a>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <form action="{{ route('deals.email-link', $deal) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">{{ $deal->link_emailed_at ? 'Email link again' : 'Email link to customer' }}</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#voidDealModal">Void deal</button>
                            <span class="text-muted small">
                                @if($deal->link_emailed_at)
                                    Emailed to {{ $deal->email }} {{ $deal->link_emailed_at->diffForHumans() }}.
                                @else
                                    Not emailed yet — sent to {{ $deal->email }}.
                                @endif
                            </span>
                        </div>

                        <hr class="my-3">
                        <div class="small">
                            @foreach(\App\Support\DealTerms::rows($deal) as $row)
                                <div class="d-flex justify-content-between {{ !empty($row[2]) ? 'fw-bold border-top pt-1 mt-1' : '' }}">
                                    <span class="text-muted">{{ $row[0] }}</span>
                                    <span>{{ $row[1] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="modal modal-blur fade" id="voidDealModal" tabindex="-1">
                    <div class="modal-dialog modal-sm modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('deals.void', $deal) }}" method="POST">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title">Void this deal?</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>The checkout link will stop working. Use this for a wrong price or customer — then create a corrected deal.</p>
                                    <label class="form-label" for="voidReason">Reason (optional)</label>
                                    <input type="text" name="reason" id="voidReason" class="form-control" maxlength="500">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Keep deal</button>
                                    <button type="submit" class="btn btn-danger ms-auto">Void deal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @elseif($deal->status === 'paid')
                <div class="alert alert-success mb-4">
                    <strong>Paid.</strong>
                    @if($deal->isRetainerDeal())
                        The monthly retainer is set up and billing runs on Paddle's schedule.
                    @else
                        The client's account has been provisioned and an invitation email sent to {{ $deal->email }}.
                    @endif
                    @if($deal->is_trial && $deal->hasRecurring())
                        {{ (float) $deal->activation_price > 0 ? 'Only the $' . number_format($deal->activation_price, 2) . ' setup fee was charged today' : 'Nothing was charged today (the card is saved)' }} —
                        Paddle won't bill the ${{ number_format($deal->agreed_monthly_price, 2) }}/mo until the trial ends.
                    @endif
                    @if($deal->isClaimed())
                        <div class="mt-2">
                            Applied to
                            @if($isAdmin)
                                <a href="{{ route('admin.subscriptions.show', $deal->subscription) }}" class="alert-link">this subscription</a>.
                            @else
                                an assistant's subscription.
                            @endif
                        </div>
                    @elseif($isAdmin && $deal->isRetainerDeal())
                        <div class="mt-2">
                            This retainer will fund the subscription of the assistant created from
                            <a href="{{ route('deals.show', $deal->parentDeal) }}" class="alert-link">the setup deal</a>.
                            <a href="{{ route('admin.subscriptions.create') }}" class="btn btn-sm btn-primary ms-2">Create subscription</a>
                        </div>
                    @elseif($isAdmin && $deal->company)
                        <div class="mt-2">
                            <a href="{{ route('admin.agents.create', ['company_id' => $deal->company->id, 'deal_id' => $deal->id]) }}" class="btn btn-sm btn-primary">
                                Add Assistant for {{ $deal->company->name }}
                            </a>
                        </div>
                    @endif
                </div>
            @elseif($deal->status === 'expired')
                <div class="alert alert-danger mb-4">
                    This deal was voided or its checkout link expired, so it can no longer be paid. Create a new deal to try again.
                </div>
            @endif

            @include('admin.partials._paddle_billing_card', ['deal' => $deal])

            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Deal Details</h3>
                    <div class="card-actions">
                        @switch($deal->status)
                            @case('paid')
                                <span class="badge bg-green-lt fs-6">Paid</span>
                                @break
                            @case('expired')
                                <span class="badge bg-red-lt fs-6">Voided / Expired</span>
                                @break
                            @default
                                <span class="badge bg-blue-lt fs-6">Awaiting payment</span>
                        @endswitch
                    </div>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Contact</div>
                            <div class="datagrid-content">{{ $deal->customer_name }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Email</div>
                            <div class="datagrid-content">{{ $deal->email }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Phone</div>
                            <div class="datagrid-content">{{ $deal->phone ?: '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Industry</div>
                            <div class="datagrid-content">{{ $deal->industry ?: '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Location</div>
                            <div class="datagrid-content">{{ collect([$deal->city, $deal->country])->filter()->implode(', ') ?: '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Website</div>
                            <div class="datagrid-content">{{ $deal->website ?: '-' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Deal type</div>
                            <div class="datagrid-content">
                                @if($deal->isRetainerDeal())
                                    Monthly retainer
                                @elseif($deal->isSetupOnly())
                                    Setup fee only
                                @else
                                    Setup + monthly retainer
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Monthly Price</div>
                            <div class="datagrid-content text-money">
                                @if($deal->isSetupOnly())
                                    <span class="text-muted">Not agreed yet</span>
                                @else
                                    ${{ number_format($deal->agreed_monthly_price, 2) }}
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Setup / Activation Fee</div>
                            <div class="datagrid-content text-money">
                                @if((float) $deal->activation_price > 0)
                                    ${{ number_format($deal->activation_price, 2) }}
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Due at checkout</div>
                            <div class="datagrid-content text-money">${{ number_format($deal->dueToday(), 2) }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Trial</div>
                            <div class="datagrid-content">
                                @if($deal->is_trial)
                                    <span class="badge bg-purple-lt">{{ $deal->trial_days }} days</span>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </div>
                        </div>
                        @if($isAdmin)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Paddle transaction</div>
                                <div class="datagrid-content"><code>{{ $deal->paddle_transaction_id ?: '-' }}</code></div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Paddle subscription</div>
                                <div class="datagrid-content"><code>{{ $deal->paddle_subscription_id ?: '-' }}</code></div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($isAdmin)
                <div class="card mb-4">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Invoices recorded in the portal</h3>
                            <div class="card-subtitle mt-1">Each one matches a Paddle transaction — compare the IDs with Paddle's dashboard.</div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Paddle transaction</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $invoice)
                                    <tr>
                                        <td><a href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                                        <td>{{ $invoice->type_label }}</td>
                                        <td class="text-money">
                                            ${{ number_format($invoice->amount, 2) }}
                                            @unless($invoice->reconcilesWithPaddle())
                                                <div class="text-red small">Paddle: ${{ number_format($invoice->paddle_charged_amount, 2) }}</div>
                                            @endunless
                                        </td>
                                        <td>
                                            <span class="badge {{ $invoice->status === 'paid' ? 'bg-green-lt' : 'bg-secondary-lt' }}">{{ ucfirst($invoice->status) }}</span>
                                        </td>
                                        <td><code class="small">{{ $invoice->paddle_transaction_id ?: '-' }}</code></td>
                                        <td>
                                            @if($invoice->paddle_transaction_id)
                                                <a href="{{ route('admin.invoices.paddle-invoice', $invoice) }}" target="_blank" class="btn btn-icon btn-ghost-primary btn-sm" title="View in Paddle">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" /><path d="M9 15l6 -6" /><path d="M11 9h4v4" /></svg>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">
                                            Nothing recorded yet — the setup-fee invoice appears the moment Paddle confirms payment, and the first retainer invoice when the trial ends and Paddle charges.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @include('admin.partials._activity_timeline', ['activity' => $activity])
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Summary</h3>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        @if(auth()->user()->can('deals.view-all'))
                            <div class="datagrid-item">
                                <div class="datagrid-title">Closer</div>
                                <div class="datagrid-content">{{ $deal->closer->full_name ?? '-' }}</div>
                            </div>
                        @endif
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">{{ $deal->created_at->format('M d, Y h:i A') }}</div>
                        </div>
                        @if($deal->company)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Customer</div>
                                <div class="datagrid-content">
                                    @if($isAdmin)
                                        <a href="{{ route('admin.companies.show', $deal->company) }}">{{ $deal->company->name }}</a>
                                    @else
                                        {{ $deal->company->name }}
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if($deal->parentDeal)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Setup deal</div>
                                <div class="datagrid-content"><a href="{{ route('deals.show', $deal->parentDeal) }}">{{ $deal->parentDeal->business_name }}</a></div>
                            </div>
                        @endif
                        @if($deal->childDeals->isNotEmpty())
                            <div class="datagrid-item">
                                <div class="datagrid-title">Retainer deal(s)</div>
                                <div class="datagrid-content">
                                    @foreach($deal->childDeals as $child)
                                        <div><a href="{{ route('deals.show', $child) }}">${{ number_format($child->agreed_monthly_price, 2) }}/mo</a>
                                            <span class="badge {{ $child->status === 'paid' ? 'bg-green-lt' : ($child->status === 'sent' ? 'bg-blue-lt' : 'bg-red-lt') }}">{{ $child->status === 'sent' ? 'awaiting payment' : $child->status }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
