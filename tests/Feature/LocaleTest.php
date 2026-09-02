<?php

declare(strict_types=1);

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
