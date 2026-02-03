<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Agent Information</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required" for="name">Agent Name</label>
                        <input type="text"
                               name="name"
                               id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="Sales Assistant"
                               value="{{ old('name', $agent->name ?? '') }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label required" for="company_id">Company</label>
                        <select name="company_id"
                                id="company_id"
                                class="form-select @error('company_id') is-invalid @enderror"
                                required>
                            <option value="">Select a company...</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}"
                                    {{ old('company_id', $agent->company_id ?? '') == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label required" for="retell_agent_id">Retell Agent ID</label>
                        <input type="text"
                               name="retell_agent_id"
                               id="retell_agent_id"
                               class="form-control @error('retell_agent_id') is-invalid @enderror"
                               placeholder="agent_xxxxxxxxxxxxx"
                               value="{{ old('retell_agent_id', $agent->retell_agent_id ?? '') }}"
                               required>
                        @error('retell_agent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-hint">The agent ID from Retell AI dashboard</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="phone_number">Phone Number</label>
                        <input type="text"
                               name="phone_number"
                               id="phone_number"
                               class="form-control @error('phone_number') is-invalid @enderror"
                               placeholder="+1 (555) 000-0000"
                               value="{{ old('phone_number', $agent->phone_number ?? '') }}">
                        @error('phone_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label required" for="cost_per_minute">Cost Per Minute</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number"
                                   name="cost_per_minute"
                                   id="cost_per_minute"
                                   class="form-control @error('cost_per_minute') is-invalid @enderror"
                                   placeholder="0.0000"
                                   step="0.0001"
                                   min="0"
                                   value="{{ old('cost_per_minute', $agent->cost_per_minute ?? '0.1000') }}"
                                   required>
                        </div>
                        @error('cost_per_minute')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-hint">Retell AI cost per minute for this agent</div>
                    </div>

                    @isset($agent)
                        <div class="col-md-6 mb-3">
                            <label class="form-label required" for="status">Status</label>
                            <select name="status"
                                    id="status"
                                    class="form-select @error('status') is-invalid @enderror"
                                    required>
                                <option value="active" {{ old('status', $agent->status) == 'active' ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="paused" {{ old('status', $agent->status) == 'paused' ? 'selected' : '' }}>
                                    Paused
                                </option>
                                <option value="archived" {{ old('status', $agent->status) == 'archived' ? 'selected' : '' }}>
                                    Archived
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endisset

                    <div class="col-12 mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea name="description"
                                  id="description"
                                  class="form-control @error('description') is-invalid @enderror"
                                  rows="3"
                                  placeholder="Describe what this agent does...">{{ old('description', $agent->description ?? '') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @isset($agent)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Agent Info</h3>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">{{ $agent->created_at->format('M d, Y') }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Total Calls</div>
                            <div class="datagrid-content">{{ number_format($agent->callLogs()->count()) }}</div>
                        </div>
                        @if($agent->subscription)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Subscription</div>
                                <div class="datagrid-content">
                                    <span class="badge bg-green-lt">{{ $agent->subscription->plan->name }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endisset

        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>
                        {{ isset($agent) ? 'Update Agent' : 'Create Agent' }}
                    </button>
                    <a href="{{ isset($agent) ? route('admin.agents.show', $agent) : route('admin.agents.index') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>

        @if(!isset($agent))
            <div class="card bg-primary-lt">
                <div class="card-body">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-primary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-1">Retell AI Integration</h4>
                            <p class="text-muted mb-0 small">
                                Make sure you have created the agent in your Retell AI dashboard first. You'll need the agent ID to link it here.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
