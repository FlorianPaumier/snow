<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->updateOrCreate(
            ['name' => 'Frostline Déneigement'],
            ['name' => 'Frostline Déneigement', 'trial_ends_at' => now()->addDays(14)],
        );

        // The organization scope hides rows without an authenticated user;
        // seeders run unscoped...
        User::query()->withoutGlobalScopes()->updateOrCreate(['email' => 'admin@snow.test'], [
            'organization_id' => $organization->id,
            'name' => 'Admin User',
            // The "hashed" cast on the model hashes this automatically.
            'password' => 'password',
            'role' => UserRole::ROLE_ADMIN,
        ]);

        User::query()->withoutGlobalScopes()->updateOrCreate(['email' => 'worker@snow.test'], [
            'organization_id' => $organization->id,
            'name' => 'Worker User',
            'password' => 'password',
            'role' => UserRole::ROLE_WORKER,
        ]);

        User::query()->withoutGlobalScopes()->updateOrCreate(['email' => 'user@snow.test'], [
            'organization_id' => $organization->id,
            'name' => 'Regular User',
            'password' => 'password',
            'role' => UserRole::ROLE_USER,
        ]);

        User::factory()->count(10)->forOrganization($organization)->create();
        User::factory()->count(5)->worker()->forOrganization($organization)->create();

        $this->callWith(ClientSeeder::class, ['organization' => $organization]);
        $this->callWith(ContractSeeder::class);
    }
}
