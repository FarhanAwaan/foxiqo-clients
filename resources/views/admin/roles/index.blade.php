@extends('layouts.admin')

@section('title', 'Roles & Permissions')

@section('page-pretitle')
    System
@endsection

@section('page-header')
    Roles & Permissions
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <p class="text-muted mb-0">
                Each role has a fixed internal identity (what the app actually checks to decide
                access) and a display <strong>label</strong> you can rename anytime — e.g. renaming
                "Closer" to "Manager" here only changes what's shown, nothing breaks. Permissions
                control finer-grained visibility beyond the built-in role split; define new ones
                below, then check them from code with <code>auth()->user()->can('your.permission')</code>.
                To grant a permission to one specific person instead of a whole role, use the
                Permissions panel on that user's page.
            </p>
        </div>
    </div>

    <!-- Define a new permission -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Define a New Permission</h3>
        </div>
        <form action="{{ route('admin.permissions.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-8 mb-3 mb-md-0">
                        <label class="form-label" for="permission_name">Permission Name</label>
                        <input type="text" name="name" id="permission_name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               placeholder="e.g. recordings.view">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-hint">Lowercase, dots/dashes/underscores only. Automatically granted to Admin.</div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Create Permission</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @foreach($roles as $role)
        <div class="card mb-4">
            @if($role->name === 'admin')
                <div class="card-header">
                    <h3 class="card-title">{{ $role->label ?? 'Administrator' }}</h3>
                    <div class="card-actions">
                        <span class="badge bg-red-lt">Always full access — not editable</span>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($permissions as $permission)
                        <span class="badge bg-green-lt me-1 mb-1">{{ $permission->name }}</span>
                    @empty
                        <p class="text-muted mb-0">No permissions defined yet.</p>
                    @endforelse
                </div>
            @else
                <div class="card-header">
                    <h3 class="card-title text-capitalize">{{ $role->label ?? $role->name }}</h3>
                    <div class="card-actions">
                        <span class="badge bg-secondary-lt">internal id: {{ $role->name }}</span>
                    </div>
                </div>
                <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required" for="label_{{ $role->id }}">Display Label</label>
                            <input type="text" name="label" id="label_{{ $role->id }}"
                                   class="form-control"
                                   value="{{ old('label', $role->label ?? $role->name) }}"
                                   required>
                            <div class="form-hint">What this role is called everywhere in the portal — rename it anytime.</div>
                        </div>

                        <label class="form-label mb-2">Permissions</label>
                        @forelse($permissions as $permission)
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                    {{ $role->permissions->contains('name', $permission->name) ? 'checked' : '' }}>
                                <span class="form-check-label">{{ $permission->name }}</span>
                            </label>
                        @empty
                            <p class="text-muted mb-0">No permissions defined yet — create one above.</p>
                        @endforelse
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            @endif
        </div>
    @endforeach
@endsection
