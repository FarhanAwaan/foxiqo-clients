<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Company Information</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label required" for="name">Company Name</label>
                        <input type="text"
                               name="name"
                               id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="Enter company name"
                               value="{{ old('name', $company->name ?? '') }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label required" for="email">Primary Email</label>
                        <input type="email"
                               name="email"
                               id="email"
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="company@example.com"
                               value="{{ old('email', $company->email ?? '') }}"
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="billing_email">Billing Email</label>
                        <input type="email"
                               name="billing_email"
                               id="billing_email"
                               class="form-control @error('billing_email') is-invalid @enderror"
                               placeholder="billing@example.com"
                               value="{{ old('billing_email', $company->billing_email ?? '') }}">
                        <div class="form-hint">Leave empty to use primary email for billing</div>
                        @error('billing_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="phone">Phone</label>
                        <input type="text"
                               name="phone"
                               id="phone"
                               class="form-control @error('phone') is-invalid @enderror"
                               placeholder="+1 (555) 000-0000"
                               value="{{ old('phone', $company->phone ?? '') }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @isset($company)
                        <div class="col-md-6 mb-3">
                            <label class="form-label required" for="status">Status</label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', $company->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="suspended" {{ old('status', $company->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                <option value="inactive" {{ old('status', $company->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endisset
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Address</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label" for="address">Street Address</label>
                        <input type="text"
                               name="address"
                               id="address"
                               class="form-control @error('address') is-invalid @enderror"
                               placeholder="123 Main Street"
                               value="{{ old('address', $company->address ?? '') }}">
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="city">City</label>
                        <input type="text"
                               name="city"
                               id="city"
                               class="form-control @error('city') is-invalid @enderror"
                               placeholder="New York"
                               value="{{ old('city', $company->city ?? '') }}">
                        @error('city')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="state">State / Province</label>
                        <input type="text"
                               name="state"
                               id="state"
                               class="form-control @error('state') is-invalid @enderror"
                               placeholder="NY"
                               value="{{ old('state', $company->state ?? '') }}">
                        @error('state')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="postal_code">Postal Code</label>
                        <input type="text"
                               name="postal_code"
                               id="postal_code"
                               class="form-control @error('postal_code') is-invalid @enderror"
                               placeholder="10001"
                               value="{{ old('postal_code', $company->postal_code ?? '') }}">
                        @error('postal_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="country">Country</label>
                        <input type="text"
                               name="country"
                               id="country"
                               class="form-control @error('country') is-invalid @enderror"
                               placeholder="United States"
                               value="{{ old('country', $company->country ?? '') }}">
                        @error('country')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Notes</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <textarea name="notes"
                              id="notes"
                              rows="6"
                              class="form-control @error('notes') is-invalid @enderror"
                              placeholder="Internal notes about this company...">{{ old('notes', $company->notes ?? '') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>
                        {{ isset($company) ? 'Update Company' : 'Create Company' }}
                    </button>
                    <a href="{{ isset($company) ? route('admin.companies.show', $company) : route('admin.companies.index') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
