<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TourDispatch;
use App\Models\TourTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TourDispatch> */
final class TourDispatchFactory extends Factory
{
    public function definition(): array
    {
        $template = TourTemplate::factory()->create();

        return ['organization_id' => $template->organization_id, 'request_key' => fake()->uuid(),
            'scheduled_date' => today()->toDateString(), 'template_ids' => [$template->id]];
    }
}
