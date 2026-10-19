<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CloseConnectedCheckout;
use App\Actions\StartConnectedCheckout;
use App\Actions\SyncConnectedPayment;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final readonly class ConnectedPaymentController
{
    public function show(Request $request, string $organization, string $payment): Response
    {
        $installment = $this->resolve($organization, $payment);

        return Inertia::render('payments/review', [
            'payment' => ['id' => $installment->id, 'organization' => $organization, 'merchant' => $installment->organization->name,
                'amount' => $installment->amount, 'slot' => $installment->slot->label(), 'paid' => $installment->paid,
                'status' => $installment->stripe_status, 'reversedAmount' => $installment->stripe_reversed_amount,
                'available' => $installment->organization->stripe_connect_id !== null && config()->string('services.stripe_connect.key') !== '' && config()->string('services.stripe_connect.webhook_secret') !== '' && $installment->amount >= 50],
            'signature' => $request->query('signature'), 'expires' => $request->query('expires'),
        ]);
    }

    public function store(string $organization, string $payment): HttpResponse
    {
        abort_if(config()->string('services.stripe_connect.key') === '' || config()->string('services.stripe_connect.webhook_secret') === '', 503);
        $url = resolve(StartConnectedCheckout::class)->handle($this->resolve($organization, $payment));

        return Inertia::location($url);
    }

    public function update(Payment $payment, SyncConnectedPayment $sync): RedirectResponse
    {
        $sync->handle($payment);

        return back();
    }

    public function destroy(Payment $payment, CloseConnectedCheckout $close): RedirectResponse
    {
        $close->handle($payment);

        return back();
    }

    private function resolve(string $organization, string $payment): Payment
    {
        $installment = Payment::query()->withoutGlobalScopes()->where('organization_id', $organization)->findOrFail($payment);
        $contract = Contract::query()->withoutGlobalScopes()->where('organization_id', $organization)->findOrFail($installment->contract_id);
        abort_unless(Client::query()->withoutGlobalScopes()->where('organization_id', $organization)->whereNotAnonymized()->whereKey($contract->client_id)->exists(), 404);

        return $installment;
    }
}
