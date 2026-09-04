<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\PlanFeatureLocked;
use App\Mail\InstallmentReminderMail;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Number;
use Illuminate\Validation\ValidationException;

final readonly class SendPaymentReminder
{
    public function __construct(
        private FlushOrganizationCache $flushCache,
        private GetPlanFeatures $planFeatures,
    ) {}

    /**
     * One-click manual reminder from the unpaid installments list. Payment
     * reminders are a Pro feature, so the free plan stops here. A manual
     * send also resets the automated reminder cooldown: the owner just
     * reached out, so the cron stays quiet for another week.
     */
    public function handle(Payment $payment): void
    {
        $payment->load(['contract.client', 'organization']);

        if ($payment->paid) {
            throw ValidationException::withMessages([
                'payment' => __('payments.already_paid'),
            ]);
        }

        $features = $this->planFeatures->handle($payment->organization);

        if (! $features['paymentReminders']) {
            throw new PlanFeatureLocked(__('statuses.feature_locked'));
        }

        $this->send($payment);

        $payment->forceFill(['last_reminder_at' => now()])->save();

        $this->flushCache->handle($payment->organization_id);
    }

    /**
     * Send the reminder email without recording any state — the caller owns
     * the reminder bookkeeping (manual vs automated policies differ). The
     * reply-to (the organization's users) rides on the mailable's envelope.
     */
    public function send(Payment $payment): void
    {
        Mail::to($this->clientEmail($payment))
            ->send(new InstallmentReminderMail($this->details($payment)));
    }

    /**
     * @return list<string>
     */
    public function replyTo(Payment $payment): array
    {
        /** @var list<string> */
        return User::query()
            ->where('organization_id', $payment->organization_id)
            ->pluck('email')
            ->all();
    }

    /**
     * @return array{clientName: string, slotLabel: string, season: string, amount: string, dueDate: string, duePhrase: string, organizationName: string, replyTo: list<string>}
     */
    public function details(Payment $payment): array
    {
        $dueDate = $payment->due_date;

        return [
            'clientName' => $payment->contract->client->displayName(),
            'slotLabel' => $payment->slot->label(),
            'season' => $payment->contract->season,
            'amount' => Number::currency($payment->amount / 100, 'CAD', locale: 'fr-CA') ?: '—',
            'dueDate' => $dueDate?->locale('fr-CA')->translatedFormat('j F Y') ?? '—',
            'duePhrase' => $this->duePhrase($dueDate),
            'organizationName' => $payment->organization->name,
            'replyTo' => $this->replyTo($payment),
        ];
    }

    private function clientEmail(Payment $payment): string
    {
        $email = $payment->contract->client->email;

        if (! is_string($email) || $email === '') {
            throw ValidationException::withMessages([
                'payment' => __('payments.no_email'),
            ]);
        }

        return $email;
    }

    /**
     * Carbon's diffInDays is signed: negative when the due date is past.
     */
    private function duePhrase(?CarbonInterface $dueDate): string
    {
        if (! $dueDate instanceof CarbonInterface) {
            return 'sans échéance';
        }

        $days = (int) today()->diffInDays($dueDate->copy()->startOfDay(), false);

        return match (true) {
            $days < 0 => sprintf('en retard de %d jour%s', abs($days), abs($days) > 1 ? 's' : ''),
            $days === 0 => 'échoit aujourd’hui',
            default => sprintf('à régler d’ici %d jour%s', $days, $days > 1 ? 's' : ''),
        };
    }
}
