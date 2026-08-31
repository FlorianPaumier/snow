<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Organization;
use Illuminate\Database\Seeder;

final class ClientSeeder extends Seeder
{
    public function run(Organization $organization): void
    {
        // Created one at a time so each factory instance derives its client
        // number from the database after the previous insert...
        foreach (range(1, 5) as $unused) {
            Client::factory()->individual()->forOrganization($organization)->create();
        }

        foreach (range(1, 3) as $unused) {
            Client::factory()->company()->forOrganization($organization)->create();
        }
    }
}
