<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SendPaymentReminder;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;

final readonly class SendPaymentReminderController
{
    public function __invoke(Payment $payment, SendPaymentReminder $action): RedirectResponse
    {
        $action->handle($payment);

        return to_route('dashboard')->with('status', __('statuses.payment_reminder_sent'));
    }
}
