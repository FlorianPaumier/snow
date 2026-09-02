<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

test('to array', function (): void {
    $user = User::factory()->create()->refresh();

    expect(array_keys($user->toArray()))
        ->toBe([
            'id',
            'name',
            'email',
            'email_verified_at',
            'two_factor_confirmed_at',
            'created_at',
            'updated_at',
            'role',
            'organization_id',
        ]);
});

test('defaults to the user role', function (): void {
    $user = User::factory()->create();

    expect($user->role)->toBe(UserRole::ROLE_USER);
});

test('factory role states', function (string $state, UserRole $expectedRole): void {
    $user = User::factory()->{$state}()->create();

    expect($user->role)->toBe($expectedRole);
})->with([
    'admin' => ['admin', UserRole::ROLE_ADMIN],
    'worker' => ['worker', UserRole::ROLE_WORKER],
]);

test('seeder creates one user per role', function (): void {
    $this->seed(DatabaseSeeder::class);

    foreach ([UserRole::ROLE_ADMIN, UserRole::ROLE_WORKER, UserRole::ROLE_USER] as $role) {
        expect(User::query()->withoutGlobalScopes()->where('role', $role)->count())->toBeGreaterThanOrEqual(1);
    }

    expect(User::query()->withoutGlobalScopes()->where('email', 'admin@snow.test')->exists())->toBeTrue();
});

test('seeded users can authenticate with the seeded password', function (string $email): void {
    $this->seed(DatabaseSeeder::class);

    $user = User::query()->withoutGlobalScopes()->where('email', $email)->firstOrFail();

    expect(Hash::check('password', $user->password))->toBeTrue();
})->with([
    'admin@snow.test',
    'worker@snow.test',
    'user@snow.test',
]);

test('seeder is idempotent', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->withoutGlobalScopes()->whereIn('email', ['admin@snow.test', 'worker@snow.test', 'user@snow.test'])->count())->toBe(3);
});
