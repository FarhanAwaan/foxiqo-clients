@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.customer')

@section('title', 'My Profile')

@section('page-pretitle')
    Account
@endsection

@section('page-header')
    My Profile
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <span class="avatar avatar-xl {{ $user->role === 'admin' ? 'bg-red-lt' : 'bg-primary-lt' }}">
                            {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                        </span>
                    </div>
                    <h3 class="card-title mb-1">{{ $user->full_name }}</h3>
                    <p class="text-muted">{{ $user->email }}</p>
                    <div class="mb-3">
                        @if($user->role === 'admin')
                            <span class="badge bg-red-lt">Administrator</span>
                        @else
                            <span class="badge bg-blue-lt">Customer</span>
                        @endif
                    </div>
                </div>
                <div class="card-body border-top">
                    <div class="datagrid">
                        @if($user->phone)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Phone</div>
                                <div class="datagrid-content">{{ $user->phone }}</div>
                            </div>
                        @endif
                        @if($user->company)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Company</div>
                                <div class="datagrid-content">{{ $user->company->name }}</div>
                            </div>
                        @endif
                        <div class="datagrid-item">
                            <div class="datagrid-title">Member Since</div>
                            <div class="datagrid-content">{{ $user->created_at->format('M d, Y') }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Last Login</div>
                            <div class="datagrid-content">
                                {{ $user->last_login_at ? $user->last_login_at->format('M d, Y h:i A') : 'Never' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Update Profile Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Update Profile</h3>
                </div>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <div class="d-flex">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                                    </div>
                                    <div>{{ session('success') }}</div>
                                </div>
                                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="first_name">First Name</label>
                                <input type="text"
                                       name="first_name"
                                       id="first_name"
                                       class="form-control @error('first_name') is-invalid @enderror"
                                       value="{{ old('first_name', $user->first_name) }}"
                                       required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="last_name">Last Name</label>
                                <input type="text"
                                       name="last_name"
                                       id="last_name"
                                       class="form-control @error('last_name') is-invalid @enderror"
                                       value="{{ old('last_name', $user->last_name) }}"
                                       required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email"
                                       class="form-control"
                                       value="{{ $user->email }}"
                                       disabled>
                                <div class="form-hint">Email address cannot be changed</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="text"
                                       name="phone"
                                       id="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       placeholder="+1 (555) 000-0000"
                                       value="{{ old('phone', $user->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Change Password</h3>
                </div>
                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label" for="current_password">Current Password</label>
                                <input type="password"
                                       name="current_password"
                                       id="current_password"
                                       class="form-control @error('current_password') is-invalid @enderror"
                                       required>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="password">New Password</label>
                                <input type="password"
                                       name="password"
                                       id="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-hint">Minimum 8 characters</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="password_confirmation">Confirm New Password</label>
                                <input type="password"
                                       name="password_confirmation"
                                       id="password_confirmation"
                                       class="form-control"
                                       required>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z" /><path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /><path d="M8 11v-4a4 4 0 1 1 8 0v4" /></svg>
                            Change Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Activity Log -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Activity</h3>
                </div>
                <div class="card-body p-0">
                    @if($activityLogs->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($activityLogs as $log)
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            @switch($log->action)
                                                @case('login')
                                                    <span class="avatar avatar-sm bg-green-lt">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 8v-2a2 2 0 0 1 2 -2h7a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-7a2 2 0 0 1 -2 -2v-2" /><path d="M3 12h13l-3 -3" /><path d="M13 15l3 -3" /></svg>
                                                    </span>
                                                    @break
                                                @case('logout')
                                                    <span class="avatar avatar-sm bg-yellow-lt">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" /><path d="M9 12h12l-3 -3" /><path d="M18 15l3 -3" /></svg>
                                                    </span>
                                                    @break
                                                @case('profile_updated')
                                                    <span class="avatar avatar-sm bg-blue-lt">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>
                                                    </span>
                                                    @break
                                                @case('password_changed')
                                                    <span class="avatar avatar-sm bg-red-lt">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z" /><path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /><path d="M8 11v-4a4 4 0 1 1 8 0v4" /></svg>
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="avatar avatar-sm bg-secondary-lt">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 8l.01 0" /><path d="M11 12l1 0l0 4l1 0" /></svg>
                                                    </span>
                                            @endswitch
                                        </div>
                                        <div class="col">
                                            <div class="d-block text-body">
                                                @switch($log->action)
                                                    @case('login')
                                                        Logged in
                                                        @break
                                                    @case('logout')
                                                        Logged out
                                                        @break
                                                    @case('profile_updated')
                                                        Updated profile information
                                                        @break
                                                    @case('password_changed')
                                                        Changed password
                                                        @break
                                                    @default
                                                        {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                                @endswitch
                                            </div>
                                            <div class="text-muted small mt-1">
                                                {{ $log->created_at->format('M d, Y h:i A') }}
                                                @if($log->ip_address)
                                                    <span class="ms-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-inline" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z" /></svg>
                                                        {{ $log->ip_address }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-auto text-muted">
                                            {{ $log->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state py-4">
                            <div class="empty-state-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 5h7" /><path d="M4 8h7" /><path d="M4 11h7" /><path d="M4 14h7" /><path d="M4 17h7" /><path d="M15 5l4 4l-4 4" /><path d="M19 9h-9" /><path d="M15 17l4 -4" /><path d="M19 17h-9" /></svg>
                            </div>
                            <p class="empty-state-title">No activity recorded</p>
                            <p class="empty-state-description">
                                Your recent account activity will appear here.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
