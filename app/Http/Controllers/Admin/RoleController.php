<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Lets an admin change what each role (currently: closer) can see, and what
 * it's called, without a deploy. Roles/permissions are seeded in
 * database/seeders/RolePermissionSeeder.php; this screen edits which
 * permissions are attached to which role, each role's display label, and
 * lets the admin define new permissions for future closer-facing screens
 * to check.
 *
 * A role's `name` (the internal slug — 'admin', 'customer', 'closer') is
 * never editable here: it's what `users.role`, isCloser(), and the
 * `role_or_permission:admin|closer` route middleware actually key off.
 * Only `label` (what's displayed) and its permission set are.
 */
class RoleController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function index(): View
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->name === 'admin') {
            return back()->with('error', 'The admin role always has full access and can\'t be edited.');
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $oldValues = $role->only('label');
        $role->update(['label' => $validated['label']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        $this->auditService->log('role_updated', $role, $oldValues);

        return back()->with('success', "\"{$role->label}\" updated.");
    }

    /**
     * Define a new permission so it can be toggled per role (or granted
     * directly to a user — see UserController::updatePermissions()). New
     * permissions are auto-granted to admin, consistent with admin always
     * having full access.
     */
    public function storePermission(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'regex:/^[a-z0-9._-]+$/', 'unique:permissions,name'],
        ]);

        $permission = Permission::create(['name' => $validated['name'], 'guard_name' => 'web']);

        Role::findByName('admin')->givePermissionTo($permission);

        $this->auditService->log('permission_created', $permission);

        return back()->with('success', "Permission \"{$permission->name}\" created — remember to actually check it (auth()->user()->can('{$permission->name}')) wherever it should gate something.");
    }
}
