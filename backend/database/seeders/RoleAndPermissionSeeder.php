<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create all permissions
        foreach (PermissionEnum::cases() as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm->value],
                [
                    'display_name' => $perm->displayName(),
                    'group' => $perm->group(),
                ]
            );
        }

        // Create all roles and assign permissions
        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::firstOrCreate(
                ['name' => $roleEnum->value],
                [
                    'display_name' => $roleEnum->displayName(),
                    'description' => $roleEnum->description(),
                    'is_system' => $roleEnum->isSystemRole(),
                ]
            );

            // Assign permissions to role
            $permissions = PermissionEnum::forRole($roleEnum);
            $permissionIds = Permission::whereIn(
                'name',
                array_map(fn($p) => $p->value, $permissions)
            )->pluck('id');

            $role->permissions()->sync($permissionIds);
        }
    }
}
