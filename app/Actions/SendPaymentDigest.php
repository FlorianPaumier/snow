<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\PaymentDigestMail;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Number;

final readonly class SendPaymentDigest
{
    /**
     * Weekly digest window: installments due within the next two weeks, or
     * already overdue, surface in the organization's inbox. Payment
     * notifications are a Pro feature, so free-plan organizations are
     * skipped. Only organizations with matching installments are emailed.
     */
    public const int DAYS_AHEAD = 14;

    public function __construct(private GetPlanFeatures $planFeatures) {}

    public function handle(): int
    {
        $cutoff = now()->addDays(self::DAYS_AHEAD)->endOfDay();

        /** @var Collection<int, Organization> $organizations */
        $organizations = Organization::query()
            ->whereHas('payments', fn (Builder $query): Builder => $query
                ->where('paid', false)
                ->whereNotNull('due_date')
                ->where('due_date', '<=', $cutoff))
            ->with(['payments' => fn (Builder $query): Builder => $query
                ->where('paid', false)
                ->whereNotNull('due_date')
                ->where('due_date', '<=', $cutoff)
                ->with('contract.client')])
            ->get();

        $notified = 0;

        foreach ($organizations as $organization) {
            $features = $this->planFeatures->handle($organization);

            if (! $features['paymentReminders']) {
                continue;
            }

            $recipients = User::query()
                ->where('organization_id', $organization->id)
                ->pluck('email')
                ->all();

            if ($recipients === []) {
                continue;
            }

            Mail::to($recipients)->send(new PaymentDigestMail([
                'organizationName' => $organization->name,
                'overdue' => $this->overdueRows($organization),
                'upcoming' => $this->upcomingRows($organization),
            ]));

            $notified++;
        }

        return $notified;
    }

    /**
     * @return list<array{clientName: string, slotLabel: string, season: string, amount: string, dueDate: string}>
     */
    private function overdueRows(Organization $organization): array
    {
        /** @var list<array{clientName: string, slotLabel: string, season: string, amount: string, dueDate: string}> */
        return $organization->payments
            ->filter(fn (Payment $payment): bool => $payment->due_date?->isBefore(today()) ?? false)
            ->sortBy('due_date')
            ->map(fn (Payment $payment): array => $this->row($payment))
            ->values()
            ->all();
    }

    /**
     * @return list<array{clientName: string, slotLabel: string, season: string, amount: string, dueDate: string, duePhrase: string}>
     */
    private function upcomingRows(Organization $organization): array
    {
        /** @var list<array{clientName: string, slotLabel: string, season: string, amount: string, dueDate: string, duePhrase: string}> */
        return $organization->payments
            ->filter(fn (Payment $payment): bool => ! ($payment->due_date?->isBefore(today()) ?? false))
            ->sortBy('due_date')
            ->map(fn (Payment $payment): array => [
                ...$this->row($payment),
                'duePhrase' => $payment->due_date === null ? 'sans échéance' : $this->upcomingPhrase($payment->due_date),
            ])
            ->values()
            ->all();
    }

    /**
     * Carbon's diffInDays is signed: the due date is always today or
     * later here.
     */
    private function upcomingPhrase(CarbonInterface $dueDate): string
    {
        $days = (int) today()->diffInDays($dueDate->copy()->startOfDay(), false);

        return $days === 0 ? 'échoit aujourd’hui' : sprintf('dans %d jour%s', $days, $days > 1 ? 's' : '');
    }

    /**
     * @return array{clientName: string, slotLabel: string, season: string, amount: string, dueDate: string}
     */
    private function row(Payment $payment): array
    {
        return [
            'clientName' => $payment->contract->client->displayName(),
            'slotLabel' => $payment->slot->label(),
            'season' => $payment->contract->season,
            'amount' => Number::currency($payment->amount / 100, 'CAD', locale: 'fr-CA') ?: '—',
            'dueDate' => $payment->due_date?->locale('fr-CA')->translatedFormat('j F Y') ?? '—',
        ];
    }
}
