<?php

declare(strict_types=1);

use App\Actions\SendPaymentDigest;
use App\Enums\PaymentSlot;
use App\Mail\PaymentDigestMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

// The digest runs from the scheduler without an authenticated user, so
// these tests stay unauthenticated to mirror the cron context (no
// organization scope filtering the cross-organization fixtures)...
beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('emails the organization a digest with overdue and upcoming installments', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create([
        'firstName' => 'Marc',
        'lastName' => 'Tremblay',
    ]);
    $contract = Contract::factory()->forClient($client)->create(['season' => '2026-2027']);
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
        'amount' => 20000,
        'paid' => false,
        'due_date' => now()->subDays(5),
    ]);
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_SECOND_INSTALLMENT,
        'amount' => 15000,
        'paid' => false,
        'due_date' => now()->addDays(3),
    ]);

    Mail::fake();

    expect(resolve(SendPaymentDigest::class)->handle())->toBe(1);

    Mail::assertQueued(PaymentDigestMail::class, fn (PaymentDigestMail $mail): bool => $mail->hasTo($this->user->email)
        && $mail->digest['organizationName'] === $this->user->organization->name
        && count($mail->digest['overdue']) === 1
        && $mail->digest['overdue'][0]['clientName'] === 'Marc Tremblay'
        && count($mail->digest['upcoming']) === 1);
});

it('skips organizations with nothing due soon or overdue', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
        'amount' => 20000,
        'paid' => false,
        'due_date' => now()->addDays(SendPaymentDigest::DAYS_AHEAD + 5),
    ]);
    // Collected and undated installments are invisible to the digest too...
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_SECOND_INSTALLMENT,
        'amount' => 10000,
        'paid' => true,
        'method' => 'cash',
        'paid_at' => now(),
    ]);

    Mail::fake();

    expect(resolve(SendPaymentDigest::class)->handle())->toBe(0);

    Mail::assertNothingQueued();
});

it('keeps each organization digest scoped to its own installments', function (): void {
    $mine = Client::factory()->forOrganization($this->user->organization)->create();
    $myContract = Contract::factory()->forClient($mine)->create();
    $myContract->payments()->create([
        'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
        'amount' => 20000,
        'paid' => false,
        'due_date' => now()->addDays(2),
    ]);

    $otherUser = User::factory()->create();
    $other = Client::factory()->forOrganization($otherUser->organization)->create([
        'firstName' => 'Marie',
        'lastName' => 'Roy',
    ]);
    $otherContract = Contract::factory()->forClient($other)->create();
    $otherContract->payments()->create([
        'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
        'amount' => 90000,
        'paid' => false,
        'due_date' => now()->subDays(4),
    ]);

    Mail::fake();

    expect(resolve(SendPaymentDigest::class)->handle())->toBe(2);

    Mail::assertQueued(PaymentDigestMail::class, 2);
    Mail::assertQueued(PaymentDigestMail::class, fn (PaymentDigestMail $mail): bool => $mail->hasTo($this->user->email)
        && $mail->digest['overdue'] === []
        && count($mail->digest['upcoming']) === 1);
});

it('skips free-plan organizations in the digest', function (): void {
    $this->user->organization->forceFill(['trial_ends_at' => now()->subDay()])->save();

    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create(['season' => '2026-2027']);
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
        'amount' => 20000,
        'paid' => false,
        'due_date' => now()->subDays(5),
    ]);

    Mail::fake();

    // The installment is overdue, but the organization is on the free
    // plan: no digest is sent...
    expect(resolve(SendPaymentDigest::class)->handle())->toBe(0);

    Mail::assertNothingQueued();
});

it('digests the trial organization while skipping a free-plan one', function (): void {
    $freeOrg = Organization::factory()->expired()->create();
    $freeClient = Client::factory()->forOrganization($freeOrg)->create();
    $freeContract = Contract::factory()->forClient($freeClient)->create(['season' => '2026-2027']);
    $freeContract->payments()->create([
        'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
        'amount' => 20000,
        'paid' => false,
        'due_date' => now()->subDays(5),
    ]);

    // The acting user's organization is on its trial (Pro) with the same
    // shape of overdue installment...
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create(['season' => '2026-2027']);
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
        'amount' => 20000,
        'paid' => false,
        'due_date' => now()->subDays(5),
    ]);

    Mail::fake();

    expect(resolve(SendPaymentDigest::class)->handle())->toBe(1);

    Mail::assertQueued(PaymentDigestMail::class, fn (PaymentDigestMail $mail): bool => $mail->hasTo($this->user->email));
});
