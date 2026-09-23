<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Keeps Spatie's role/permission tables in sync with the `users.role` enum,
 * which stays the source of truth for coarse admin/customer/closer access
 * (unchanged, existing middleware) — role `name` is that fixed internal slug.
 * Spatie is layered on top specifically so the closer role's finer-grained
 * access AND its display `label` (e.g. renaming "Closer" to "Manager") are
 * admin-editable from Admin > Roles & Permissions, without a deploy.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin' => 'Administrator',
            'customer' => 'Customer',
            'closer' => 'Closer',
        ];

        foreach ($roles as $roleName => $label) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['label' => $label]
            );
        }
        $roles = array_keys($roles);

        // The only permissions this build actually enforces (DealController/DealService).
        // Admin > Roles & Permissions can grow this list later without new plumbing.
        $permissions = [
            'deals.view-all',
            'deals.override-price-floor',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        Role::findByName('admin')->syncPermissions($permissions);
        Role::findByName('customer')->syncPermissions([]);
        Role::findByName('closer')->syncPermissions([]);

        // Keep every existing user's Spatie role in sync with their `role` column.
        User::whereIn('role', $roles)->each(function (User $user) {
            $user->syncRoles([$user->role]);
        });
    }
}
