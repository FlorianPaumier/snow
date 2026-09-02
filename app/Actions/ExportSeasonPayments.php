<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ClientType;
use App\Enums\PaymentSlot;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use Generator;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class ExportSeasonPayments
{
    /**
     * CSV column headers, one installment per data row.
     *
     * @return list<string>
     */
    public static function headers(): array
    {
        return [
            'Saison',
            'Référence contrat',
            'Date du contrat',
            'Nº client',
            'Client',
            'Type',
            'Adresse',
            'Ville',
            'Courriel',
            'Téléphone',
            'Échéance',
            'Date d\'échéance',
            'Montant (CAD)',
            'Payé',
            'Date de paiement',
            'Méthode',
        ];
    }

    /**
     * Every installment of the season — or of all seasons when none is
     * given — as flat CSV rows carrying the client and contract columns
     * the accountant needs to match payments against contracts.
     *
     * @return Generator<int, list<string>>
     */
    public function handle(?string $season = null): Generator
    {
        $contracts = Contract::query()
            ->with('client', 'payments')
            ->when($season !== null, fn (Builder $query): Builder => $query->where('season', $season))
            ->orderByDesc('season')
            ->orderBy('created_at')
            ->cursor();

        foreach ($contracts as $contract) {
            foreach ($this->paymentsInSlotOrder($contract) as $payment) {
                yield $this->row($contract, $payment);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function row(Contract $contract, Payment $payment): array
    {
        $client = $contract->client;

        return [
            $contract->season,
            $contract->id,
            $contract->created_at->toDateString(),
            $client->clientNumber,
            $client->displayName(),
            $this->clientType($client),
            $client->address_street,
            $client->address_city,
            $client->email ?? '',
            $client->phoneDay ?? $client->cellPhone ?? '',
            $payment->slot->label(),
            $payment->due_date?->toDateString() ?? '',
            $this->amount($payment->amount),
            $payment->paid ? 'Oui' : 'Non',
            $payment->paid_at?->toDateString() ?? '',
            $payment->method?->label() ?? '',
        ];
    }

    /**
     * Payments ordered by their slot's natural order (Dépôt → Solde),
     * same convention as the contract page.
     *
     * @return Collection<int, Payment>
     */
    private function paymentsInSlotOrder(Contract $contract): Collection
    {
        $order = array_map(fn (PaymentSlot $slot): string => $slot->value, PaymentSlot::cases());

        return $contract->payments
            ->sortBy(fn (Payment $payment): int|false => array_search($payment->slot->value, $order, true))
            ->values();
    }

    private function clientType(Client $client): string
    {
        return match ($client->type) {
            ClientType::CLIENT_COMPANY => 'Entreprise',
            ClientType::CLIENT_INDIVIDUAL => 'Particulier',
        };
    }

    /**
     * Cents to a decimal string — CSV-friendly, locale-independent.
     */
    private function amount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
