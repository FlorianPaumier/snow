<?php

declare(strict_types=1);

use App\Actions\GrantAccessUnlock;
use App\Exceptions\OrganizationReadOnly;
use App\Models\Organization;
use App\Models\User;

beforeEach(function (): void {
    $this->organization = Organization::factory()->expired()->create();
    $this->admin = User::factory()->admin()->forOrganization($this->organization)->create();
});

it('grants a 24h unlock to a read-only organization', function (): void {
    $this->actingAs($this->admin);

    resolve(GrantAccessUnlock::class)->handle($this->admin);

    expect($this->organization->fresh()->access_unlock_until->toIso8601String())
        ->toBe(now()->addHours(24)->toIso8601String());
});

it('refuses to grant the unlock twice', function (): void {
    $this->organization->forceFill(['access_unlock_until' => now()->addHours(24)])->save();

    resolve(GrantAccessUnlock::class)->handle($this->admin);
})->throws(OrganizationReadOnly::class);

it('refuses the unlock once the first one has expired', function (): void {
    $this->organization->forceFill(['access_unlock_until' => now()->subHour()])->save();

    resolve(GrantAccessUnlock::class)->handle($this->admin);
})->throws(OrganizationReadOnly::class);

it('refuses the unlock when the org already has full access', function (): void {
    $organization = Organization::factory()->create(); // trial running...

    $admin = User::factory()->admin()->forOrganization($organization)->create();

    $this->actingAs($admin);

    resolve(GrantAccessUnlock::class)->handle($admin);
})->throws(OrganizationReadOnly::class);
