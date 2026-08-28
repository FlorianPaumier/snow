<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@snow.test'],
            [
                'name' => 'Admin User',
                // The "hashed" cast on the model hashes this automatically.
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ],
        );

        User::updateOrCreate(
            ['email' => 'worker@snow.test'],
            [
                'name' => 'Worker User',
                'password' => 'password',
                'role' => User::ROLE_WORKER,
            ],
        );

        User::updateOrCreate(
            ['email' => 'user@snow.test'],
            [
                'name' => 'Regular User',
                'password' => 'password',
                'role' => User::ROLE_USER,
            ],
        );

        User::factory()->count(10)->create();
        User::factory()->count(5)->worker()->create();
    }
}
