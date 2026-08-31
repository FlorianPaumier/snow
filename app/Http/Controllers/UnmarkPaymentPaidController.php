<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UnmarkPaymentPaid;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;

final readonly class UnmarkPaymentPaidController
{
    public function __invoke(Payment $payment, UnmarkPaymentPaid $action): RedirectResponse
    {
        $action->handle($payment);

        return to_route('contracts.show', ['contract' => $payment->contract_id])->with('status', 'Encaissement annulé.');
    }
}
