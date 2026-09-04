<?php

declare(strict_types=1);

use App\Actions\SendAutomatedPaymentReminders;
use App\Actions\SendPaymentReminder;
use App\Enums\PaymentSlot;
use App\Mail\InstallmentReminderMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

// The organization scope requires an authenticated user to see any data;
// contracts and payments must belong to that user's organization...
beforeEach(function (): void {
    $this->user = User::factory()->create();

    Auth::login($this->user);
});

/**
 * An unpaid installment with the given due date (absolute or relative to
 * the frozen test now).
 */
function scheduledPayment(Client $client, CarbonInterface|string|null $dueDate, array $attributes = []): Payment
{
    // Contracts are unique per (client, season): stagger the season so a
    // single fixture client can hold several installments...
    static $sequence = 0;
    $season = sprintf('%04d-%04d', 2000 + ++$sequence, 2001 + $sequence);
    $contract = Contract::factory()->forClient($client)->create(['season' => $season]);

    return $contract->payments()->create([
        'slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT,
        'amount' => 20000,
        'paid' => false,
        'due_date' => $dueDate instanceof CarbonInterface ? $dueDate->toDateString() : $dueDate,
        ...$attributes,
    ]);
}

it('reminds installments due within the week and overdue ones', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $dueSoon = scheduledPayment($client, now()->addDays(5));
    $overdue = scheduledPayment($client, now()->subDays(10));

    Mail::fake();

    $sent = resolve(SendAutomatedPaymentReminders::class)->handle();

    expect($sent)->toBe(2);

    Mail::assertQueued(InstallmentReminderMail::class, 2);

    $dueSoon->refresh();
    $overdue->refresh();
    expect($dueSoon->automated_reminders_sent)->toBe(1)
        ->and($dueSoon->last_reminder_at?->equalTo(now()->startOfSecond()))->toBeTrue()
        ->and($overdue->automated_reminders_sent)->toBe(1);
});

it('reminds installments due exactly at the policy boundary', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    scheduledPayment($client, now()->addDays(SendAutomatedPaymentReminders::DAYS_BEFORE_DUE));

    Mail::fake();

    expect(resolve(SendAutomatedPaymentReminders::class)->handle())->toBe(1);

    Mail::assertQueued(InstallmentReminderMail::class, 1);
});

it('ignores installments that are not yet due within the week', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    scheduledPayment($client, now()->addDays(SendAutomatedPaymentReminders::DAYS_BEFORE_DUE + 1));

    Mail::fake();

    expect(resolve(SendAutomatedPaymentReminders::class)->handle())->toBe(0);

    Mail::assertNothingQueued();
});

it('ignores installments without a due date, collected ones, and clients without email', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $noDueDate = scheduledPayment($client, null);
    $collected = scheduledPayment($client, now()->subDays(2), ['paid' => true, 'method' => 'cash', 'paid_at' => now()]);

    $otherClient = Client::factory()->forOrganization($this->user->organization)->withoutEmail()->create();
    $noEmail = scheduledPayment($otherClient, now()->subDays(2));

    Mail::fake();

    expect(resolve(SendAutomatedPaymentReminders::class)->handle())->toBe(0);

    Mail::assertNothingQueued();
    expect($noDueDate->fresh()->last_reminder_at)->toBeNull()
        ->and($collected->fresh()->last_reminder_at)->toBeNull()
        ->and($noEmail->fresh()->last_reminder_at)->toBeNull();
});

it('ignores clients whose email is an empty string', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create(['email' => '']);
    $payment = scheduledPayment($client, now()->subDays(2));

    Mail::fake();

    expect(resolve(SendAutomatedPaymentReminders::class)->handle())->toBe(0);

    Mail::assertNothingQueued();
    expect($payment->fresh()->last_reminder_at)->toBeNull()
        ->and($payment->fresh()->automated_reminders_sent)->toBe(0);
});

it('respects the weekly cooldown between automated reminders', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $cooldown = scheduledPayment($client, now()->subDays(3), [
        'last_reminder_at' => now()->subDays(SendAutomatedPaymentReminders::COOLDOWN_DAYS - 1),
        'automated_reminders_sent' => 1,
    ]);
    $elapsed = scheduledPayment($client, now()->subDays(20), [
        'last_reminder_at' => now()->subDays(SendAutomatedPaymentReminders::COOLDOWN_DAYS),
        'automated_reminders_sent' => 1,
    ]);

    Mail::fake();

    expect(resolve(SendAutomatedPaymentReminders::class)->handle())->toBe(1);

    $cooldown->refresh();
    $elapsed->refresh();
    expect($cooldown->automated_reminders_sent)->toBe(1)
        ->and($elapsed->automated_reminders_sent)->toBe(2);
});

it('stops after the maximum number of automated reminders', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $capped = scheduledPayment($client, now()->subDays(30), [
        'automated_reminders_sent' => SendAutomatedPaymentReminders::MAX_AUTOMATED_REMINDERS,
    ]);
    $belowCap = scheduledPayment($client, now()->subDays(30), [
        'automated_reminders_sent' => SendAutomatedPaymentReminders::MAX_AUTOMATED_REMINDERS - 1,
    ]);

    Mail::fake();

    expect(resolve(SendAutomatedPaymentReminders::class)->handle())->toBe(1);

    expect($capped->fresh()->automated_reminders_sent)
        ->toBe(SendAutomatedPaymentReminders::MAX_AUTOMATED_REMINDERS)
        ->and($belowCap->fresh()->automated_reminders_sent)
        ->toBe(SendAutomatedPaymentReminders::MAX_AUTOMATED_REMINDERS);
});

it('a manual reminder suppresses the automated pass for the cooldown window', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $payment = scheduledPayment($client, now()->subDays(2));

    Mail::fake();

    resolve(SendPaymentReminder::class)->handle($payment);
    expect(resolve(SendAutomatedPaymentReminders::class)->handle())->toBe(0);

    Mail::assertQueued(InstallmentReminderMail::class, 1);
});

it('skips free-plan organizations in the automated pass', function (): void {
    // The authenticated org is on trial (Pro); a second, expired-trial org
    // must not have its installments reminded. The action runs from the
    // scheduler where no organization scope applies, so the cross-org
    // eager loads are read scope-free like the cron would...
    Auth::logout();

    $freeOrg = Organization::factory()->expired()->create();
    $freeClient = Client::factory()->forOrganization($freeOrg)->create(['email' => 'free-client@example.com']);
    scheduledPayment($freeClient, now()->addDays(5));

    $proClient = Client::factory()->forOrganization($this->user->organization)->create();
    $dueSoon = scheduledPayment($proClient, now()->addDays(5));

    Mail::fake();

    $sent = resolve(SendAutomatedPaymentReminders::class)->handle();

    // Only the trialing organization's installment was reminded...
    expect($sent)->toBe(1);

    Mail::assertQueued(InstallmentReminderMail::class, 1);
    Mail::assertNotQueued(InstallmentReminderMail::class, fn (InstallmentReminderMail $mail): bool => $mail->hasTo('free-client@example.com'));

    $dueSoon->refresh();
    expect($dueSoon->automated_reminders_sent)->toBe(1)
        ->and($freeClient->contracts()->sole()->payments()->sole()->fresh()->automated_reminders_sent)->toBe(0);
});
