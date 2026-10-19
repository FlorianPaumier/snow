<?php

declare(strict_types=1);

use App\Actions\AnonymizeClient;
use App\Actions\GenerateContractPdf;
use App\Actions\GeneratePaymentReceiptPdf;
use App\Actions\UpdateClient;
use App\Actions\UpdateContract;
use App\Enums\PaymentSlot;
use App\Mail\InstallmentReminderMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->user = User::factory()->admin()->create();
    $this->actingAs($this->user);
    Storage::fake(config()->string('filesystems.default'));
    $this->client = Client::factory()->forOrganization($this->user->organization)->create();
    $this->contract = Contract::factory()->forClient($this->client)->create();
    $this->service = $this->contract->services()->create(['label' => 'Snow', 'amount' => 40000, 'position' => 1]);
    foreach (PaymentSlot::cases() as $slot) {
        $this->contract->payments()->create(['slot' => $slot, 'amount' => 10000, 'paid' => false]);
    }

    $this->payload = [
        'clientId' => $this->client->id, 'season' => $this->contract->season,
        'services' => [['id' => $this->service->id, 'label' => 'Snow', 'amount' => 40000]],
        'payments' => array_fill_keys(array_column(PaymentSlot::cases(), 'value'), ['amount' => 10000, 'dueDate' => null]),
    ];
});

it('rejects foreign missing and duplicate service IDs before changing the contract', function (string $kind): void {
    $id = match ($kind) {
        'foreign' => Contract::factory()->create()->services()->create(['label' => 'Other', 'amount' => 1, 'position' => 1])->id,
        'missing' => 999999,
        default => $this->service->id,
    };
    $this->payload['services'][0]['id'] = $id;
    if ($kind === 'duplicate') {
        $this->payload['services'][] = $this->payload['services'][0];
    }

    expect(fn () => resolve(UpdateContract::class)->handle($this->contract, $this->payload))->toThrow(ValidationException::class);
    expect($this->service->fresh()->amount)->toBe(40000)
        ->and($this->contract->services()->count())->toBe(1)
        ->and($this->contract->payments()->sum('amount'))->toBe(40000);
})->with(['foreign', 'missing', 'duplicate']);

it('rejects an edit when a payment was collected after the form was loaded', function (): void {
    $this->contract->load('payments');
    $this->contract->payments()->where('slot', 'deposit')->update(['paid' => true, 'paid_at' => now()]);
    $this->payload['payments']['deposit']['amount'] = 20000;
    $this->payload['services'][0]['amount'] = 50000;
    expect(fn () => resolve(UpdateContract::class)->handle($this->contract, $this->payload))->toThrow(ValidationException::class);
    expect($this->service->fresh()->amount)->toBe(40000)
        ->and($this->contract->payments()->sum('amount'))->toBe(40000);
});

it('rejects inconsistent totals inside the action', function (): void {
    $this->payload['services'][0]['amount'] = 1;
    expect(fn () => resolve(UpdateContract::class)->handle($this->contract, $this->payload))->toThrow(ValidationException::class);
    expect($this->service->fresh()->amount)->toBe(40000);
});

it('returns validation feedback for malformed service rows', function (mixed $row): void {
    $this->payload['services'] = [$row];
    $this->putJson(route('contracts.update', $this->contract), $this->payload)->assertUnprocessable()->assertJsonValidationErrors('services.0');
})->with(['string' => ['bad'], 'number' => [12], 'null' => [null]]);

it('does not restore identity from a stale edit after anonymization', function (): void {
    $stale = $this->client->fresh();
    resolve(AnonymizeClient::class)->handle($this->client);
    expect(fn () => resolve(UpdateClient::class)->handle($stale, []))->toThrow(ValidationException::class);
    expect($this->client->fresh()->firstName)->toBe('')->and($this->client->fresh()->email)->toBeNull();
});

it('reloads identity under a transaction before generating either PDF', function (bool $receipt): void {
    $this->client->update(['firstName' => 'ErasedIdentity123']);
    $staleName = $this->client->firstName;
    $this->contract->load('client');
    $payment = $this->contract->payments()->first();
    $payment->load('contract.client');

    resolve(AnonymizeClient::class)->handle($this->client);
    Http::fake(function ($request) use ($staleName) {
        expect(DB::transactionLevel())->toBeGreaterThan(1);
        expect($request->body())->not->toContain($staleName);

        return Http::response('%PDF redacted');
    });
    $path = $receipt ? resolve(GeneratePaymentReceiptPdf::class)->handle($payment) : resolve(GenerateContractPdf::class)->handle($this->contract);
    Storage::disk(config()->string('filesystems.default'))->assertExists($path);
})->with([false, true]);

it('removes PDFs generated before anonymization', function (): void {
    Http::fake(['*' => Http::response('%PDF with identity')]);
    $payment = $this->contract->payments()->first();
    $contractPath = resolve(GenerateContractPdf::class)->handle($this->contract);
    $receiptPath = resolve(GeneratePaymentReceiptPdf::class)->handle($payment);
    resolve(AnonymizeClient::class)->handle($this->client);
    Storage::disk(config()->string('filesystems.default'))->assertMissing([$contractPath, $receiptPath]);
});

it('suppresses delayed reminder delivery for settled anonymized missing and processing payments', function (string $state): void {
    $payment = $this->contract->payments()->first();
    $mail = unserialize(serialize(new InstallmentReminderMail($payment->id, $payment->organization_id)));
    match ($state) {
        'paid' => $payment->update(['paid' => true]),
        'anonymized' => resolve(AnonymizeClient::class)->handle($this->client),
        'deleted' => $payment->delete(),
        'no_email' => $this->client->update(['email' => null]),
        default => $payment->forceFill(['stripe_status' => 'processing'])->save(),
    };
    expect($mail->send(Mail::mailer()))->toBeNull();
    expect(Mail::mailer()->getSymfonyTransport()->messages())->toHaveCount(0);
})->with(['paid', 'anonymized', 'deleted', 'processing', 'no_email']);

it('builds delayed reminders using the current recipient and amount', function (): void {
    $payment = $this->contract->payments()->first();
    $mail = unserialize(serialize(new InstallmentReminderMail($payment->id, $payment->organization_id)));
    $this->client->update(['email' => 'updated@example.com', 'firstName' => 'Current']);
    $payment->update(['amount' => 20000]);
    $mail->send(Mail::mailer());
    expect($mail->hasTo('updated@example.com'))->toBeTrue()
        ->and($mail->reminder['clientName'])->toContain('Current')
        ->and($mail->reminder['amount'])->toContain('200');
});

it('discards legacy queued reminder snapshots without sending personal data', function (): void {
    $mail = new ReflectionClass(InstallmentReminderMail::class)->newInstanceWithoutConstructor();
    $mail->reminder = ['clientName' => 'Old private name'];

    expect($mail->send(Mail::mailer()))->toBeNull();
    expect(Mail::mailer()->getSymfonyTransport()->messages())->toHaveCount(0);
});

it('accepts valid integer strings without treating a paid amount as changed', function (): void {
    $this->contract->payments()->where('slot', 'deposit')->update(['paid' => true, 'paid_at' => now()]);
    foreach (array_keys($this->payload['payments']) as $slot) {
        $this->payload['payments'][$slot]['amount'] = '10000';
    }

    $this->payload['services'][0]['amount'] = '40000';
    $this->put(route('contracts.update', $this->contract), $this->payload)->assertSessionHasNoErrors()->assertRedirect();
    expect($this->contract->payments()->sum('amount'))->toBe(40000);
});
