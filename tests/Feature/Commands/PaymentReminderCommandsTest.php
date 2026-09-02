<?php

declare(strict_types=1);

use App\Enums\PaymentSlot;
use App\Mail\InstallmentReminderMail;
use App\Mail\PaymentDigestMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('sends automated reminders via the scheduled command', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->forOrganization($user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
        'amount' => 20000,
        'paid' => false,
        'due_date' => now()->addDays(3),
    ]);

    Mail::fake();

    $this->artisan('payments:send-reminders')
        ->expectsOutputToContain('Automated reminders sent: 1')
        ->assertSuccessful();

    Mail::assertQueued(InstallmentReminderMail::class, 1);
});

it('emails weekly digests via the scheduled command', function (): void {
    $user = User::factory()->create();
    $client = Client::factory()->forOrganization($user->organization)->create();
    $contract = Contract::factory()->forClient($client)->create();
    $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
        'amount' => 20000,
        'paid' => false,
        'due_date' => now()->addDays(10),
    ]);

    Mail::fake();

    $this->artisan('payments:send-digest')
        ->expectsOutputToContain('Digests sent: 1')
        ->assertSuccessful();

    Mail::assertQueued(PaymentDigestMail::class, 1);
});
