<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactInquiryStatus;
use App\Models\ContactInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<ContactInquiry>
 */
final class ContactInquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'company' => null,
            'fleetSize' => null,
            'message' => fake()->sentence(12),
        ];
    }

    public function withCompany(): self
    {
        return $this->state(fn (): array => [
            'company' => fake()->company(),
            'fleetSize' => fake()->randomElement(['1-3', '4-10', '11-50', '50+']),
        ]);
    }

    public function sent(): self
    {
        return $this->state(fn (): array => [
            'status' => ContactInquiryStatus::STATUS_SENT,
            'sent_at' => Date::now(),
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (): array => [
            'status' => ContactInquiryStatus::STATUS_FAILED,
            'failed_at' => Date::now(),
        ]);
    }
}
