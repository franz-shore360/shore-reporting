<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DummyUsersSeeder extends Seeder
{
    /**
     * Create 10,000 dummy users (factory: random names, unique emails, password: "password").
     *
     * Run: php artisan db:seed --class=DummyUsersSeeder
     * (Use Laragon PHP 8.2+ if your default `php` is older.)
     */
    public function run(): void
    {
        $total = 10_000;
        $chunk = 500;

        $this->command?->info("Creating {$total} dummy users in chunks of {$chunk}…");

        for ($i = 0; $i < $total; $i += $chunk) {
            $count = min($chunk, $total - $i);
            User::factory()->count($count)->create();
            $this->command?->info('  … '.min($i + $chunk, $total).' / '.$total);
        }

        $this->command?->info('Done.');
    }
}
