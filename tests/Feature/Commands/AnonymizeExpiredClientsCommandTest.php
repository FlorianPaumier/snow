<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Contract;
use App\Models\User;

function expiredClient(User $user, string $season): Client
{
    $client = Client::factory()->forOrganization($user->organization)->create();
    Contract::factory()->forClient($client)->create(['season' => $season]);

    return $client;
}

it('anonymizes contracted clients past the retention window and keeps recent ones', function (): void {
    $this->travelTo(now()->setDate(2031, 7, 2));
    $user = User::factory()->create();

    // Last season ended 2025-06-30; the 6-year window expired 2031-06-30...
    $expired = expiredClient($user, '2024-2025');
    // This season is still inside the window...
    $recent = expiredClient($user, '2030-2031');

    $this->artisan('clients:anonymize-expired')->assertSuccessful();

    expect($expired->fresh()->isAnonymized())->toBeTrue()
        ->and($expired->fresh()->email)->toBeNull()
        ->and($expired->fresh()->contracts()->count())->toBe(1)
        ->and($recent->fresh()->isAnonymized())->toBeFalse()
        ->and($recent->fresh()->email)->not->toBeNull();
});

it('erases contractless clients past the window and keeps the rest', function (): void {
    $this->travelTo(now()->setDate(2031, 7, 2));
    $user = User::factory()->create();

    // A prospect entered years ago and never contracted...
    $oldProspect = Client::factory()->forOrganization($user->organization)->create();
    $oldProspect->forceFill(['created_at' => now()->subYears(7)])->save();
    // A fresh prospect...
    $newProspect = Client::factory()->forOrganization($user->organization)->create();
    // Already anonymized clients are left alone...
    $already = Client::factory()->forOrganization($user->organization)->create([
        'anonymized_at' => now()->subDay(),
    ]);

    $this->artisan('clients:anonymize-expired')
        ->expectsOutputToContain('0 anonymized, 1 erased')
        ->assertSuccessful();

    expect($oldProspect->fresh())->toBeNull()
        ->and($newProspect->fresh())->not->toBeNull()
        ->and($already->fresh()->anonymized_at->isYesterday())->toBeTrue();
});

it('changes nothing in dry-run mode', function (): void {
    $this->travelTo(now()->setDate(2031, 7, 2));
    $user = User::factory()->create();

    $expired = expiredClient($user, '2024-2025');
    $oldProspect = Client::factory()->forOrganization($user->organization)->create();
    $oldProspect->forceFill(['created_at' => now()->subYears(7)])->save();

    $this->artisan('clients:anonymize-expired', ['--dry-run' => true])
        ->expectsOutputToContain('would be anonymized')
        ->assertSuccessful();

    expect($expired->fresh()->isAnonymized())->toBeFalse()
        ->and($oldProspect->fresh())->not->toBeNull();
});
