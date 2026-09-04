<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Payment;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class SendAutomatedPaymentReminders
{
    /**
     * Anti-spam policy: only installments with an explicit due date are
     * reminded, no earlier than a week before it, at most once a week, and
     * never more than three automated emails per installment. A manual
     * reminder resets the cooldown by refreshing last_reminder_at.
     */
    public const int DAYS_BEFORE_DUE = 7;

    public const int COOLDOWN_DAYS = 7;

    public const int MAX_AUTOMATED_REMINDERS = 3;

    public function __construct(
        private SendPaymentReminder $reminders,
        private FlushOrganizationCache $flushCache,
        private GetPlanFeatures $planFeatures,
    ) {}

    public function handle(): int
    {
        // Due dates are dates but may be stored with a time component, so
        // the window is closed with an end-of-day bound...
        /** @var Collection<int, Payment> $payments */
        $payments = Payment::query()
            ->where('paid', false)
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->addDays(self::DAYS_BEFORE_DUE)->endOfDay())
            ->where('automated_reminders_sent', '<', self::MAX_AUTOMATED_REMINDERS)
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('last_reminder_at')
                ->orWhere('last_reminder_at', '<=', now()->subDays(self::COOLDOWN_DAYS)))
            ->with(['contract.client', 'organization'])
            ->get();

        $sent = 0;
        $flushed = [];

        foreach ($payments as $payment) {
            // Payment reminders are a Pro feature: free-plan organizations
            // are silently skipped by the automated pass...
            $features = $this->planFeatures->handle($payment->organization);

            if (! $features['paymentReminders']) {
                continue;
            }

            $email = $payment->contract->client->email;
            if (! is_string($email)) {
                continue;
            }

            if ($email === '') {
                continue;
            }

            $this->reminders->send($payment);

            $payment->forceFill([
                'last_reminder_at' => now(),
                'automated_reminders_sent' => $payment->automated_reminders_sent + 1,
            ])->save();

            $flushed[$payment->organization_id] = true;
            $sent++;
        }

        foreach (array_keys($flushed) as $organizationId) {
            $this->flushCache->handle($organizationId);
        }

        return $sent;
    }
}
