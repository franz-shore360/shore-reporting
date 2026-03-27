<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Ensure the Admin role exists. If any user already has Admin, do nothing.
     * Otherwise assign Admin to the first user (by id) who has no roles at all.
     *
     * @return void
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin', 'guard_name' => 'web']
        );

        if (User::role('Admin')->exists()) {
            return;
        }

        $user = User::query()
            ->whereDoesntHave('roles')
            ->orderBy('id')
            ->first();

        if ($user !== null) {
            $user->assignRole($adminRole);
        }
    }
}
