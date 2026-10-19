<?php

declare(strict_types=1);

use App\Mail\OrganizationInviteCodeMail;
use App\Models\User;
use App\ValueObjects\Season;
use Illuminate\Support\Facades\App;

it('defaults the locale to French without a cookie', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    expect(App::getLocale())->toBe('fr');
});

it('applies the locale from the cookie', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'en')
        ->get(route('dashboard'))
        ->assertOk();

    expect(App::getLocale())->toBe('en');
});

it('falls back to French for an unsupported locale cookie', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'de')
        ->get(route('dashboard'))
        ->assertOk();

    expect(App::getLocale())->toBe('fr');
});

it('shares the locale with Inertia pages', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'en')
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'en'));
});

it('translates validation messages in the active locale', function (): void {
    $user = User::factory()->admin()->create();

    // English cookie: the required message resolves in English...
    $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'en')
        ->post(route('contracts.store'), [
            'clientId' => null,
            'season' => Season::current()->label,
            'services' => [['label' => 'Service', 'amount' => 10000]],
            'payments' => [
                'deposit' => 10000,
                'first' => 0,
                'second' => 0,
                'balance' => 0,
            ],
        ])
        ->assertSessionHasErrors('clientId');

    expect(session('errors')->get('clientId'))->toBe(['The client field is required.']);
});

it('translates validation messages in French by default', function (): void {
    $user = User::factory()->admin()->create();

    // No cookie: the French default applies...
    $this->actingAs($user)
        ->post(route('contracts.store'), [
            'clientId' => null,
            'season' => Season::current()->label,
            'services' => [['label' => 'Service', 'amount' => 10000]],
            'payments' => [
                'deposit' => 10000,
                'first' => 0,
                'second' => 0,
                'balance' => 0,
            ],
        ])
        ->assertSessionHasErrors('clientId');

    expect(session('errors')->get('clientId'))->toBe(['Le champ client est obligatoire.']);
});

it('uses French for invitation emails and keeps English available', function (string $locale, string $subject): void {
    app()->setLocale($locale);
    $mail = new OrganizationInviteCodeMail('TESTCODE', '2026-12-31');
    expect($mail->envelope()->subject)->toBe($subject)
        ->and($mail->locale)->toBe($locale);
    $html = $mail->render();
    expect($html)->toContain($locale === 'fr' ? 'Créer votre compte' : 'Create your account');
})->with([['fr', 'Votre code d’invitation'], ['en', 'Your invitation code']]);

it('uses the requested locale for error pages outside route middleware', function (string $locale, string $title): void {
    $this->withUnencryptedCookie('locale', $locale)->get('/missing-locale-page')->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('error')->where('title', $title));
})->with([['fr', 'Page introuvable.'], ['en', 'Page not found.']]);
