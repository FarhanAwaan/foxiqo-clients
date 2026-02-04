<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Plan Details</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label required">Plan Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $plan->name ?? '') }}" required placeholder="e.g., Basic, Professional, Enterprise">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                              rows="3" placeholder="Brief description of this plan">{{ old('description', $plan->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label required">Monthly Price ($)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="price" step="0.01" min="0"
                                       class="form-control @error('price') is-invalid @enderror"
                                       value="{{ old('price', $plan->price ?? '0.00') }}" required>
                            </div>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label required">Included Minutes</label>
                            <div class="input-group">
                                <input type="number" name="included_minutes" min="0"
                                       class="form-control @error('included_minutes') is-invalid @enderror"
                                       value="{{ old('included_minutes', $plan->included_minutes ?? '0') }}" required>
                                <span class="input-group-text">min</span>
                            </div>
                            @error('included_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Overage Rate</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="overage_rate" step="0.0001" min="0"
                                       class="form-control @error('overage_rate') is-invalid @enderror"
                                       value="{{ old('overage_rate', $plan->overage_rate ?? '') }}" placeholder="0.0000">
                                <span class="input-group-text">/min</span>
                            </div>
                            <small class="text-muted">Charged per minute over the included limit</small>
                            @error('overage_rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Plan Configuration</h3>
            </div>
            <div class="card-body">
                @if(!isset($plan) || !$plan->exists)
                    <!-- Only show custom plan option on create -->
                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input type="checkbox" name="is_custom" value="1" class="form-check-input"
                                   id="isCustomSwitch" {{ old('is_custom') ? 'checked' : '' }}>
                            <span class="form-check-label">Custom Plan</span>
                        </label>
                        <small class="text-muted d-block">Custom plans are only available to a specific company</small>
                    </div>

                    <div class="mb-3" id="companySelect" style="{{ old('is_custom') ? '' : 'display: none;' }}">
                        <label class="form-label required">Company</label>
                        <select name="company_id" class="form-select @error('company_id') is-invalid @enderror">
                            <option value="">Select Company</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @else
                    <!-- Show status toggle on edit -->
                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                   {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}>
                            <span class="form-check-label">Active</span>
                        </label>
                        <small class="text-muted d-block">Inactive plans cannot be assigned to new subscriptions</small>
                    </div>

                    @if($plan->is_custom)
                        <div class="alert alert-info mb-0">
                            <div class="d-flex">
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>
                                </div>
                                <div>
                                    <h4 class="alert-title">Custom Plan</h4>
                                    <div class="text-muted">For: {{ $plan->company->name ?? 'Unknown' }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
            <div class="card-footer">
                <div class="d-flex">
                    <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">
                        {{ isset($plan) && $plan->exists ? 'Update Plan' : 'Create Plan' }}
                    </button>
                </div>
            </div>
        </div>

        @if(isset($plan) && $plan->exists)
            <div class="card bg-light">
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">{{ $plan->created_at->format('M d, Y') }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Last Updated</div>
                            <div class="datagrid-content">{{ $plan->updated_at->format('M d, Y') }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Active Subscriptions</div>
                            <div class="datagrid-content">{{ $plan->subscriptions()->where('status', 'active')->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('isCustomSwitch')?.addEventListener('change', function() {
        document.getElementById('companySelect').style.display = this.checked ? '' : 'none';
    });
</script>
@endpush
