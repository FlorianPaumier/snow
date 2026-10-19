<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SyncConnectedPayment;
use App\Models\Payment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

#[Description('Refresh connected-account settlements, refunds and disputes from Stripe')]
#[Signature('payments:reconcile-stripe')]
final class ReconcileConnectedPaymentsCommand extends Command
{
    public function handle(): int
    {
        $payments = Payment::query()->withoutGlobalScopes()->whereNotNull('stripe_checkout_key')
            ->where(fn (Builder $query): Builder => $query
                ->where(fn (Builder $pending): Builder => $pending->where('paid', false)
                    ->where(fn (Builder $status): Builder => $status->whereNull('stripe_status')->orWhereNotIn('stripe_status', ['expired', 'failed'])))
                ->orWhere('paid_at', '>=', now()->subDays(30))
                ->orWhere('stripe_checkout_started_at', '>=', now()->subDays(30)));

        if (config()->string('services.stripe_connect.key') === '') {
            return $payments->exists() ? self::FAILURE : self::SUCCESS;
        }

        $sync = resolve(SyncConnectedPayment::class);
        $failed = false;
        foreach ($payments->lazyById() as $payment) {
            try {
                $sync->handle($payment);
            } catch (Throwable $exception) {
                report($exception);
                $this->error('Could not reconcile installment '.$payment->id);
                $failed = true;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
