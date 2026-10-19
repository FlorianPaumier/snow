<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Tour;
use App\Models\TourNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TourNotification> */
final class TourNotificationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $client = Client::factory()->create();
        $tour = Tour::factory()->create(['organization_id' => $client->organization_id, 'started_at' => now(),
            'stops' => [['id' => $client->id, 'name' => $client->displayName(), 'address' => 'Depot', 'remark' => null]]]);

        return ['organization_id' => $client->organization_id, 'tour_id' => $tour->id, 'client_id' => $client->id,
            'recipient_key' => hash('sha256', mb_strtolower((string) $client->email)), 'event' => 'started', 'locale' => 'fr'];
    }
}
