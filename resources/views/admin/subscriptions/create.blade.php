@extends('layouts.admin')

@section('title', 'Create Subscription')

@section('page-pretitle')
    Subscriptions
@endsection

@section('page-header')
    Create New Subscription
@endsection

@section('content')
    <form action="{{ route('admin.subscriptions.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Subscription Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required">Company</label>
                            <select name="company_id" id="companySelect" class="form-select @error('company_id') is-invalid @enderror" required>
                                <option value="">Select Company</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id', request('company_id')) == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Agent</label>
                            <select name="agent_id" id="agentSelect" class="form-select @error('agent_id') is-invalid @enderror" required>
                                <option value="">Select Agent</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" data-company="{{ $agent->company_id }}" {{ old('agent_id', request('agent_id')) == $agent->id ? 'selected' : '' }}>
                                        {{ $agent->name }} {{ $agent->phone_number ? "({$agent->phone_number})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Only agents without an existing subscription are shown</small>
                            @error('agent_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Plan</label>
                            <select name="plan_id" id="planSelect" class="form-select @error('plan_id') is-invalid @enderror" required>
                                <option value="">Select Plan</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}"
                                            data-price="{{ $plan->price }}"
                                            data-minutes="{{ $plan->included_minutes }}"
                                            {{ old('plan_id', request('plan_id')) == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }} - ${{ number_format($plan->price, 2) }}/mo ({{ number_format($plan->included_minutes) }} min)
                                    </option>
                                @endforeach
                            </select>
                            @error('plan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Custom Price (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="custom_price" step="0.01" min="0"
                                       class="form-control @error('custom_price') is-invalid @enderror"
                                       value="{{ old('custom_price') }}" placeholder="Leave blank to use plan price">
                                <span class="input-group-text">/mo</span>
                            </div>
                            <small class="text-muted">Override the plan's default price for this subscription</small>
                            @error('custom_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Summary</h3>
                    </div>
                    <div class="card-body">
                        <div id="summaryPlaceholder">
                            <p class="text-muted">Select a plan to see subscription summary</p>
                        </div>
                        <div id="summaryContent" style="display: none;">
                            <div class="datagrid">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Plan</div>
                                    <div class="datagrid-content" id="summaryPlan">-</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Monthly Price</div>
                                    <div class="datagrid-content text-money" id="summaryPrice">-</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Included Minutes</div>
                                    <div class="datagrid-content" id="summaryMinutes">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex">
                            <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary ms-auto">
                                Create Subscription
                            </button>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>
                        </div>
                        <div>
                            <h4 class="alert-title">Note</h4>
                            <div class="text-muted">
                                The subscription will be created in <strong>Pending</strong> status. You'll need to activate it to start the billing period and generate the first invoice.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const companySelect = document.getElementById('companySelect');
        const agentSelect = document.getElementById('agentSelect');
        const planSelect = document.getElementById('planSelect');
        const customPriceInput = document.querySelector('input[name="custom_price"]');

        // Filter agents by company
        companySelect.addEventListener('change', function() {
            const companyId = this.value;
            const options = agentSelect.querySelectorAll('option');

            options.forEach(option => {
                if (option.value === '') return;
                const agentCompany = option.dataset.company;
                option.style.display = (!companyId || agentCompany === companyId) ? '' : 'none';
            });

            // Reset agent selection if current selection is hidden
            const currentAgent = agentSelect.querySelector('option:checked');
            if (currentAgent && currentAgent.style.display === 'none') {
                agentSelect.value = '';
            }
        });

        // Update summary when plan changes
        function updateSummary() {
            const selectedPlan = planSelect.options[planSelect.selectedIndex];
            const placeholder = document.getElementById('summaryPlaceholder');
            const content = document.getElementById('summaryContent');

            if (!planSelect.value) {
                placeholder.style.display = '';
                content.style.display = 'none';
                return;
            }

            placeholder.style.display = 'none';
            content.style.display = '';

            const price = parseFloat(selectedPlan.dataset.price) || 0;
            const minutes = parseInt(selectedPlan.dataset.minutes) || 0;
            const customPrice = parseFloat(customPriceInput.value);

            document.getElementById('summaryPlan').textContent = selectedPlan.textContent.split(' - ')[0];
            document.getElementById('summaryPrice').textContent = '$' + (customPrice || price).toFixed(2) + '/mo';
            document.getElementById('summaryMinutes').textContent = minutes.toLocaleString() + ' min';

            if (customPrice && customPrice !== price) {
                document.getElementById('summaryPrice').innerHTML = '<span class="text-primary">$' + customPrice.toFixed(2) + '</span>/mo <span class="text-muted small">(custom)</span>';
            }
        }

        planSelect.addEventListener('change', updateSummary);
        customPriceInput.addEventListener('input', updateSummary);

        // Trigger initial filter if company is pre-selected
        if (companySelect.value) {
            companySelect.dispatchEvent(new Event('change'));
        }

        // Initial summary update
        updateSummary();
    });
</script>
@endpush
