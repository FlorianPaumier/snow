<?php

declare(strict_types=1);

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
        ]);
});

test('defaults to the user role', function (): void {
    $user = User::factory()->create();

    expect($user->role)->toBe(User::ROLE_USER);
});

test('factory role states', function (string $state, string $expectedRole): void {
    $user = User::factory()->{$state}()->create();

    expect($user->role)->toBe($expectedRole);
})->with([
    'admin' => ['admin', User::ROLE_ADMIN],
    'worker' => ['worker', User::ROLE_WORKER],
]);

test('seeder creates one user per role', function (): void {
    $this->seed(DatabaseSeeder::class);

    foreach ([User::ROLE_ADMIN, User::ROLE_WORKER, User::ROLE_USER] as $role) {
        expect(User::query()->where('role', $role)->count())->toBeGreaterThanOrEqual(1);
    }

    expect(User::query()->where('email', 'admin@snow.test')->exists())->toBeTrue();
});

test('seeded users can authenticate with the seeded password', function (string $email): void {
    $this->seed(DatabaseSeeder::class);

    $user = User::query()->where('email', $email)->firstOrFail();

    expect(Hash::check('password', $user->password))->toBeTrue();
})->with([
    'admin@snow.test',
    'worker@snow.test',
    'user@snow.test',
]);

test('seeder is idempotent', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->whereIn('email', ['admin@snow.test', 'worker@snow.test', 'user@snow.test'])->count())->toBe(3);
});
