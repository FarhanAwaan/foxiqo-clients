{{--
    Delete Assistant — permanently removes this agent and everything tied to it.
    Two gates before the form can submit: an explicit "Continue" past the
    itemized warning, then typing the assistant's exact name. See
    AgentController::destroy() / deletionSummary() for what's actually deleted
    and for the Paddle-managed guard mirrored in $summary['paddle_blocked'].
--}}
@php $summary = $deletionSummary; @endphp

<div class="card border-danger mt-3">
    <div class="card-header bg-danger-lt">
        <h3 class="card-title text-danger">Danger Zone</h3>
    </div>
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h4 class="mb-1">Delete Assistant</h4>
                <p class="text-muted mb-0">Permanently removes {{ $agent->name }} and all of its data — call logs, subscription, invoices, appointments. This cannot be undone.</p>
            </div>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAgentModal">
                Delete Assistant
            </button>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="deleteAgentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.agents.destroy', $agent) }}" method="POST" id="deleteAgentForm">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Assistant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($summary['paddle_blocked'])
                        <div class="alert alert-warning mb-0">
                            <strong>{{ $agent->name }}</strong>'s subscription is Paddle-managed and still active —
                            Paddle is still charging the customer's card for it. Cancel the subscription first
                            (that cancels it in Paddle too), then come back here to delete the assistant.
                            @if($subscriptionUrl)
                                <a href="{{ $subscriptionUrl }}" class="d-block mt-2 fw-bold">Go to Cancel Subscription &rarr;</a>
                            @endif
                        </div>
                    @else
                        {{-- Step 1: itemized warning --}}
                        <div id="deleteStep1">
                            <p>
                                This permanently deletes <strong>{{ $agent->name }}</strong>
                                ({{ $agent->company->name }}@if($agent->company->is_demo) &middot; <span class="text-muted">demo company</span>@endif)
                                and everything tied to it:
                            </p>
                            <ul class="mb-3">
                                <li>{{ $summary['call_logs'] }} call log(s) and transcript(s)</li>
                                @if($summary['has_subscription'])
                                    <li>Its subscription @if($summary['paddle_managed'])<span class="text-muted">(already cancelled/expired in Paddle)</span>@endif</li>
                                @endif
                                <li>{{ $summary['invoices'] }} invoice(s), {{ $summary['payments'] }} payment(s), {{ $summary['payment_links'] }} payment link(s) and {{ $summary['payment_receipts'] }} uploaded receipt(s)</li>
                                <li>{{ $summary['billing_cycles'] }} billing history snapshot(s)</li>
                                <li>{{ $summary['appointments'] }} booked appointment(s)</li>
                                @if($summary['has_calendar_connection'])
                                    <li>Its connected calendar</li>
                                @endif
                                @if($summary['access_grants'])
                                    <li>{{ $summary['access_grants'] }} closer/manager access grant(s) to this assistant</li>
                                @endif
                            </ul>
                            <div class="alert alert-info small mb-0">
                                This only removes it from the portal. The agent (and its phone number) still
                                exists in <strong>Retell</strong> until you also remove it there.
                            </div>
                        </div>

                        {{-- Step 2: type-to-confirm, revealed by Continue --}}
                        <div id="deleteStep2" class="d-none">
                            <p class="mb-2">Type <strong>{{ $agent->name }}</strong> to confirm — this cannot be undone.</p>
                            <input type="text" name="confirm_name" id="deleteConfirmInput" class="form-control" autocomplete="off" placeholder="{{ $agent->name }}">
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    @if($summary['paddle_blocked'])
                        <button type="button" class="btn btn-secondary ms-auto" data-bs-dismiss="modal">Close</button>
                    @else
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="deleteStep1Next" class="btn btn-outline-danger ms-auto">Continue</button>
                        <button type="submit" id="deleteStep2Submit" class="btn btn-danger ms-auto d-none" disabled>Permanently Delete</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const step1 = document.getElementById('deleteStep1');
    const step2 = document.getElementById('deleteStep2');
    const nextBtn = document.getElementById('deleteStep1Next');
    const submitBtn = document.getElementById('deleteStep2Submit');
    const input = document.getElementById('deleteConfirmInput');
    const expected = @json($agent->name);
    const modalEl = document.getElementById('deleteAgentModal');

    if (!nextBtn || !step1 || !step2 || !submitBtn || !input) return;

    nextBtn.addEventListener('click', function () {
        step1.classList.add('d-none');
        step2.classList.remove('d-none');
        nextBtn.classList.add('d-none');
        submitBtn.classList.remove('d-none');
        input.focus();
    });

    input.addEventListener('input', function () {
        submitBtn.disabled = input.value !== expected;
    });

    // Reset to step 1 whenever the modal is reopened, so a previous typed
    // confirmation can't be resubmitted without re-reading the warning.
    modalEl?.addEventListener('hidden.bs.modal', function () {
        step1.classList.remove('d-none');
        step2.classList.add('d-none');
        nextBtn.classList.remove('d-none');
        submitBtn.classList.add('d-none');
        submitBtn.disabled = true;
        input.value = '';
    });
});
</script>
@endpush
