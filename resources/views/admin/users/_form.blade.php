<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">User Information</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required" for="first_name">First Name</label>
                        <input type="text"
                               name="first_name"
                               id="first_name"
                               class="form-control @error('first_name') is-invalid @enderror"
                               placeholder="John"
                               value="{{ old('first_name', $user->first_name ?? '') }}"
                               required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label required" for="last_name">Last Name</label>
                        <input type="text"
                               name="last_name"
                               id="last_name"
                               class="form-control @error('last_name') is-invalid @enderror"
                               placeholder="Doe"
                               value="{{ old('last_name', $user->last_name ?? '') }}"
                               required>
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label required" for="email">Email Address</label>
                        <input type="email"
                               name="email"
                               id="email"
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="john@example.com"
                               value="{{ old('email', $user->email ?? '') }}"
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if(!isset($user))
                            <div class="form-hint">An invitation email will be sent to this address</div>
                        @endif
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="text"
                               name="phone"
                               id="phone"
                               class="form-control @error('phone') is-invalid @enderror"
                               placeholder="+1 (555) 000-0000"
                               value="{{ old('phone', $user->phone ?? '') }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Access & Permissions</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required" for="company_id">Company</label>
                        <select name="company_id"
                                id="company_id"
                                class="form-select @error('company_id') is-invalid @enderror"
                                {{ isset($user) && $user->role === 'admin' ? '' : 'required' }}>
                            <option value="">Select a company...</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}"
                                    {{ old('company_id', $user->company_id ?? $selectedCompanyId ?? '') == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-hint">Admin users may not require a company</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label required" for="role">Role</label>
                        <select name="role"
                                id="role"
                                class="form-select @error('role') is-invalid @enderror"
                                required>
                            <option value="customer" {{ old('role', $user->role ?? 'customer') == 'customer' ? 'selected' : '' }}>
                                Customer
                            </option>
                            <option value="admin" {{ old('role', $user->role ?? '') == 'admin' ? 'selected' : '' }}>
                                Administrator
                            </option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @isset($user)
                        <div class="col-md-6 mb-3">
                            <label class="form-label required" for="status">Status</label>
                            <select name="status"
                                    id="status"
                                    class="form-select @error('status') is-invalid @enderror"
                                    required>
                                <option value="pending" {{ old('status', $user->status) == 'pending' ? 'selected' : '' }}>
                                    Pending
                                </option>
                                <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="suspended" {{ old('status', $user->status) == 'suspended' ? 'selected' : '' }}>
                                    Suspended
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endisset
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        @isset($user)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Account Info</h3>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">{{ $user->created_at->format('M d, Y') }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Last Login</div>
                            <div class="datagrid-content">{{ $user->last_login_at ? $user->last_login_at->format('M d, Y h:i A') : 'Never' }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Email Verified</div>
                            <div class="datagrid-content">
                                @if($user->email_verified_at)
                                    <span class="badge bg-green-lt">Verified</span>
                                @else
                                    <span class="badge bg-yellow-lt">Not Verified</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endisset

        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>
                        {{ isset($user) ? 'Update User' : 'Create User' }}
                    </button>
                    <a href="{{ isset($user) ? route('admin.users.show', $user) : route('admin.users.index') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>

        @if(!isset($user))
            <div class="card bg-primary-lt">
                <div class="card-body">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-primary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9h.01" /><path d="M11 12h1v4h1" /></svg>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-1">Invitation Process</h4>
                            <p class="text-muted mb-0 small">
                                After creating the user, an invitation email will be sent to their email address.
                                They will need to set their password to activate their account.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
