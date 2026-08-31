<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
final class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $contract = Contract::factory()->create();

        return [
            'organization_id' => $contract->organization_id,
            'contract_id' => $contract->id,
            'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
            'amount' => fake()->randomElement([15000, 20000, 25000]),
            'paid' => false,
            'method' => null,
            'paid_at' => null,
        ];
    }

    public function forContract(Contract $contract): self
    {
        return $this->state(fn (): array => [
            'organization_id' => $contract->organization_id,
            'contract_id' => $contract->id,
        ]);
    }

    public function paid(): self
    {
        return $this->state(fn (): array => [
            'paid' => true,
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'paid_at' => now()->subDays(fake()->numberBetween(1, 90)),
        ]);
    }

    public function unpaid(): self
    {
        return $this->state(fn (): array => [
            'paid' => false,
            'method' => null,
            'paid_at' => null,
        ]);
    }

    public function slot(PaymentSlot $slot): self
    {
        return $this->state(fn (): array => [
            'slot' => $slot,
        ]);
    }

    public function deposit(): self
    {
        return $this->slot(PaymentSlot::SLOT_DEPOSIT);
    }

    public function firstInstallment(): self
    {
        return $this->slot(PaymentSlot::SLOT_FIRST_INSTALLMENT);
    }

    public function secondInstallment(): self
    {
        return $this->slot(PaymentSlot::SLOT_SECOND_INSTALLMENT);
    }

    public function balance(): self
    {
        return $this->slot(PaymentSlot::SLOT_BALANCE);
    }
}
