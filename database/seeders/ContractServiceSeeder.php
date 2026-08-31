<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class ContractServiceSeeder extends Seeder
{
    /**
     * Contract services are seeded together with their contract in
     * ContractSeeder; this seeder exists to keep the artisan seeder
     * list aligned with the domain models.
     */
    public function run(): void
    {
        //
    }
}
