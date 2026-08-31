<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\MarkPaymentPaid;
use App\Enums\PaymentMethod;
use App\Http\Requests\MarkPaymentPaidRequest;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;

final readonly class MarkPaymentPaidController
{
    public function __invoke(MarkPaymentPaidRequest $request, Payment $payment, MarkPaymentPaid $action): RedirectResponse
    {
        $action->handle($payment, PaymentMethod::from($request->string('method')->value()));

        return to_route('contracts.show', ['contract' => $payment->contract_id])->with('status', 'Versement encaissé.');
    }
}
