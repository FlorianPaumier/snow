<?php

declare(strict_types=1);

use App\Actions\CreateOrganization;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

it('creates an organization with its admin owner and a trial', function (): void {
    Event::fake([Registered::class]);

    $organization = app(CreateOrganization::class)->handle([
        'name' => 'Acme Déneigement',
        'userName' => 'Owner Name',
        'email' => 'owner@acme.test',
    ], 'password1234');

    expect($organization->name)->toBe('Acme Déneigement')
        ->and($organization->trial_ends_at)->not->toBeNull()
        ->and($organization->trial_ends_at->diffInDays(now()))->toBeLessThanOrEqual(14);

    $user = User::withoutGlobalScopes()->where('email', 'owner@acme.test')->firstOrFail();

    expect($user->organization_id)->toBe($organization->id)
        ->and($user->role)->toBe(UserRole::ROLE_ADMIN)
        ->and(Hash::check('password1234', $user->password))->toBeTrue();

    Event::assertDispatched(Registered::class);
});

it('rolls back the organization when the user creation fails', function (): void {
    // Duplicate email: the user insert fails inside the transaction...
    User::factory()->create(['email' => 'dupe@acme.test']);

    try {
        app(CreateOrganization::class)->handle([
            'name' => 'Acme Déneigement',
            'userName' => 'Owner Name',
            'email' => 'dupe@acme.test',
        ], 'password1234');

        $this->fail('The duplicate email should have failed.');
    } catch (QueryException) {
        // Expected...
    }

    expect(Organization::withoutGlobalScopes()->where('name', 'Acme Déneigement')->exists())->toBeFalse();
});
