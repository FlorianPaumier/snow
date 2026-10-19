<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Quote;
use App\ValueObjects\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Quote> */
final class QuoteFactory extends Factory
{
    public function definition(): array
    {
        return ['client_id' => Client::factory(),
            'organization_id' => fn (array $attributes): string => Client::query()->withoutGlobalScopes()->whereKey($attributes['client_id'])->firstOrFail()->organization_id,
            'season' => Season::current()->label,
            'services' => [['label' => 'Snow removal', 'amount' => 40000]],
            'payments' => collect(['deposit', 'first', 'second', 'balance'])->mapWithKeys(fn (string $slot): array => [$slot => ['amount' => 10000, 'dueDate' => null]])->all()];
    }
}
