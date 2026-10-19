<?php

declare(strict_types=1);

use App\Enums\PaymentSlot;
use App\Mail\InstallmentReminderMail;
use App\Mail\LoginChallengeCodeMail;
use App\Mail\PaymentDigestMail;
use App\Mail\UserInvitationMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractService;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    Auth::login($this->user);
});

it('resolves a client latest contract', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();

    $older = Contract::query()->create(['season' => '2023-2024', 'client_id' => $client->id, 'organization_id' => $client->organization_id]);
    $newer = Contract::query()->create(['season' => '2025-2026', 'client_id' => $client->id, 'organization_id' => $client->organization_id]);

    expect($client->lastContract()->is($newer))->toBeTrue()
        ->and($client->lastContract()->is($older))->toBeFalse();
});

it('falls back to the creation year for a malformed season label', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();

    // The season column is a free string: a label that escapes the
    // YYYY-YYYY shape must not break the retention anchor...
    $contract = Contract::query()->create([
        'season' => 'ete-2026',
        'client_id' => $client->id,
        'organization_id' => $client->organization_id,
        'created_at' => '2024-11-18 09:00:00',
        'updated_at' => '2024-11-18 09:00:00',
    ]);

    expect($contract->seasonEndDate()->equalTo(Date::create(2024, 6, 30, 23, 59, 59)))->toBeTrue();
});

it('anchors the season end date on the season end year', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();

    $contract = Contract::query()->create(['season' => '2025-2026', 'client_id' => $client->id, 'organization_id' => $client->organization_id]);

    expect($contract->seasonEndDate()->equalTo(Date::create(2026, 6, 30, 23, 59, 59)))->toBeTrue();
});

it('resolves a contract service relations', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();

    $contract = Contract::query()->create(['season' => '2025-2026', 'client_id' => $client->id, 'organization_id' => $client->organization_id]);

    $service = ContractService::query()->create([
        'contract_id' => $contract->id,
        'organization_id' => $contract->organization_id,
        'label' => 'Déneigement entrée',
        'amount' => 50000,
        'position' => 1,
    ]);

    expect($service->contract()->is($contract))->toBeTrue()
        ->and($service->organization()->is($this->user->organization))->toBeTrue();
});

it('resolves a payment relations', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();

    $contract = Contract::query()->create(['season' => '2025-2026', 'client_id' => $client->id, 'organization_id' => $client->organization_id]);

    $payment = Payment::query()->create([
        'contract_id' => $contract->id,
        'organization_id' => $contract->organization_id,
        'slot' => PaymentSlot::SLOT_DEPOSIT,
        'amount' => 10000,
        'paid' => false,
    ]);

    expect($payment->contract()->is($contract))->toBeTrue()
        ->and($payment->organization()->is($this->user->organization))->toBeTrue();
});

it('resolves an organization relations', function (): void {
    $organization = $this->user->organization;

    expect($organization->users()->count())->toBeInt()
        ->and($organization->clients()->count())->toBeInt()
        ->and($organization->contracts()->count())->toBeInt()
        ->and($organization->subscriptions())->toBeInstanceOf(HasMany::class);
});

it('renders the login challenge mailable', function (): void {
    $mail = new LoginChallengeCodeMail('654321');

    expect($mail->envelope()->subject)->toBe(__('invitations.login_subject'))
        ->and($mail->content()->markdown)->toBe('mail.login-challenge-code')
        ->and($mail->content()->with)->toBe(['code' => '654321'])
        ->and($mail->render())->toContain('654321');
});

it('renders the user invitation mailable', function (): void {
    $mail = new UserInvitationMail(
        email: 'invitee@example.com',
        organizationName: 'Frostline OPS',
        token: 'invitation-token',
        inviterName: 'Marie Tremblay',
    );

    $rendered = $mail->render();

    expect($mail->envelope()->subject)->toBe(__('invitations.user_subject', ['organization' => 'Frostline OPS', 'app' => config('app.name')]))
        ->and($mail->content()->markdown)->toBe('mail.user-invitation')
        ->and($mail->content()->with['email'])->toBe('invitee@example.com')
        ->and($mail->content()->with['url'])->toBe(route('user-invitation.create', ['token' => 'invitation-token'], absolute: false).'?email='.urlencode('invitee@example.com'))
        ->and($rendered)->toContain('Frostline OPS')
        ->and($rendered)->toContain('Marie Tremblay')
        ->and($rendered)->toContain(urlencode('invitee@example.com'));
});

it('renders the installment reminder mailable', function (): void {
    $mail = new InstallmentReminderMail(1, 'test');
    $mail->reminder = [
        'clientName' => 'Marc Tremblay',
        'slotLabel' => '1er versement',
        'season' => '2026-2027',
        'amount' => '200,00 $',
        'dueDate' => '1er décembre 2026',
        'duePhrase' => 'échoit aujourd’hui',
        'organizationName' => 'Frostline OPS',
        'replyTo' => ['ops@frostline.example'],
    ];

    $rendered = $mail->render();

    expect($mail->envelope()->subject)->toBe('Rappel de paiement — 1er versement (200,00 $)')
        ->and(array_map(fn (Address $address): string => $address->address, $mail->envelope()->replyTo))->toBe(['ops@frostline.example'])
        ->and($mail->content()->markdown)->toBe('mail.installment-reminder')
        ->and($rendered)->toContain('Marc Tremblay')
        ->and($rendered)->toContain('200,00 $');
});

it('renders the payment digest mailable', function (): void {
    $mail = new PaymentDigestMail([
        'organizationName' => 'Frostline OPS',
        'overdue' => [
            ['clientName' => 'Marc Tremblay', 'slotLabel' => '1er versement', 'season' => '2026-2027', 'amount' => '200,00 $', 'dueDate' => '29 août 2026'],
        ],
        'upcoming' => [
            ['clientName' => 'Marie Roy', 'slotLabel' => '2e versement', 'season' => '2026-2027', 'amount' => '150,00 $', 'dueDate' => '6 septembre 2026', 'duePhrase' => 'dans 3 jours'],
        ],
    ]);

    $rendered = $mail->render();

    expect($mail->envelope()->subject)->toBe('Paiements — 1 en retard, 1 à venir')
        ->and($mail->content()->markdown)->toBe('mail.payment-digest')
        ->and($rendered)->toContain('Marc Tremblay')
        ->and($rendered)->toContain('Marie Roy');
});
