<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;

final readonly class GetPaymentReceipt
{
    /**
     * Build the view data for the printable receipt of a collected
     * installment, shared by the on-screen document page and the
     * queued PDF generation.
     *
     * @return array<string, mixed>
     */
    public function handle(Payment $payment): array
    {
        $payment->load(['contract.client', 'organization']);

        /** @var Contract $contract */
        $contract = $payment->contract;

        return [
            // Overridden to true by the on-screen document page only; the
            // PDF render must never include the print toolbar...
            'toolbar' => false,
            'issuedAt' => now(),
            'organization' => [
                'name' => $payment->organization->name,
            ],
            'client' => $this->clientData($contract->client),
            'contract' => [
                'id' => $contract->id,
                'season' => $contract->season,
                'createdAt' => $contract->created_at,
            ],
            'payment' => [
                'slot' => $payment->slot->value,
                'amount' => $payment->amount,
                'method' => $payment->method?->value,
                'paidAt' => $payment->paid_at,
            ],
        ];
    }

    /**
     * @return array{number: string, name: string, address: array{street: string, city: string, postalCode: string, country: string}, phone: string|null, email: string|null}
     */
    private function clientData(Client $client): array
    {
        return [
            'number' => $client->clientNumber,
            'name' => $client->displayName(),
            'address' => [
                'street' => $client->address->street,
                'city' => $client->address->city,
                'postalCode' => $client->address->postalCode,
                'country' => $client->address->country,
            ],
            'phone' => $client->cellPhone ?? $client->phoneDay,
            'email' => $client->email,
        ];
    }
}
