<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Create the Admin role and assign it to all existing users.
     *
     * @return void
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin', 'guard_name' => 'web']
        );

        User::query()->each(function (User $user) use ($adminRole): void {
            if (! $user->hasRole($adminRole)) {
                $user->assignRole($adminRole);
            }
        });
    }
}
