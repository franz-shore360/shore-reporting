<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class PermissionsSeeder extends Seeder
{
    private const GUARD_NAME = 'web';

    private const ADMIN_ROLE_NAME = 'Admin';

    /**
     * Sync permissions from config/permissions.php into the database
     * and assign all of them to the Admin role.
     *
     * @return void
     */
    public function run(): void
    {
        $groups = config('permissions', []);
        $permissionNames = [];

        foreach ($groups as $group) {
            foreach ($group['permissions'] ?? [] as $permissionName => $config) {
                Permission::firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => self::GUARD_NAME]
                );
                $permissionNames[] = $permissionName;
            }
        }

        $adminRole = Role::firstOrCreate(
            ['name' => self::ADMIN_ROLE_NAME, 'guard_name' => self::GUARD_NAME]
        );
        $adminRole->syncPermissions($permissionNames);
    }
}
