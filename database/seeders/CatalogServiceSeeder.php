<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CatalogService;
use App\Models\Organization;
use Illuminate\Database\Seeder;

final class CatalogServiceSeeder extends Seeder
{
    public function run(Organization $organization): void
    {
        foreach (['Déneigement entrée' => 42500, 'Déneigement stationnement' => 15000, 'Déglaçage' => 7500] as $label => $amount) {
            CatalogService::query()->withoutGlobalScopes()->firstOrCreate(
                ['organization_id' => $organization->id, 'label' => $label],
                ['amount' => $amount],
            );
        }
    }
}
