@extends('layouts.auth')

@section('title', 'Complete Your Registration')

@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">Complete Your Registration</h2>

            <div class="text-center mb-4">
                <span class="avatar avatar-lg bg-primary-lt mb-3">
                    {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                </span>
                <h3 class="mb-1">{{ $user->full_name }}</h3>
                <p class="text-muted">{{ $user->email }}</p>
                @if($user->company)
                    <span class="badge bg-blue-lt">{{ $user->company->name }}</span>
                @endif
            </div>

            <form method="POST" action="{{ route('signup.complete', $token) }}" autocomplete="off">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="password">Create Password</label>
                    <div class="input-group input-group-flat">
                        <input type="password"
                               name="password"
                               id="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Enter a strong password"
                               autocomplete="new-password"
                               required
                               autofocus>
                        <span class="input-group-text">
                            <a href="#" class="link-secondary" title="Show password" onclick="togglePassword(event, 'password')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                            </a>
                        </span>
                    </div>
                    <div class="form-hint">Password must be at least 8 characters</div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label" for="password_confirmation">Confirm Password</label>
                    <div class="input-group input-group-flat">
                        <input type="password"
                               name="password_confirmation"
                               id="password_confirmation"
                               class="form-control"
                               placeholder="Confirm your password"
                               autocomplete="new-password"
                               required>
                        <span class="input-group-text">
                            <a href="#" class="link-secondary" title="Show password" onclick="togglePassword(event, 'password_confirmation')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                            </a>
                        </span>
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                        Activate My Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="text-center text-muted mt-3">
        Already have an account? <a href="{{ route('login') }}">Sign in</a>
    </div>
@endsection

@push('scripts')
<script>
    function togglePassword(e, inputId) {
        e.preventDefault();
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }
</script>
@endpush
