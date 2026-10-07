<?php

declare(strict_types=1);

use App\Actions\ImportUsersFromCsvAction;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it('imports every valid user row from the csv fixture', function (): void {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    Event::fake();
    $csv = file_get_contents(base_path('tests/Fixtures/users-example.csv'));

    $result = resolve(ImportUsersFromCsvAction::class)->handle(
        $csv,
        ['name' => 'name', 'email' => 'email', 'role' => 'role'],
        true,
    );

    expect($result['rows'])->toHaveCount(5)
        ->and($result['rows'][0]['errors'])->toBe([])
        ->and(User::query()->where('email', 'marie.tremblay@example.com')->value('name'))->toBe('Marie Tremblay')
        ->and(User::query()->where('organization_id', $admin->organization_id)->count())->toBe(6);
});

it('does not import invalid user rows', function (): void {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    Event::fake();

    $result = resolve(ImportUsersFromCsvAction::class)->handle(
        "name;email;role\nInvalid User;not-an-email;worker\n",
        ['name' => 'name', 'email' => 'email', 'role' => 'role'],
        true,
    );

    expect($result['rows'][0]['errors'])->not->toBeEmpty()
        ->and(User::query()->where('email', 'not-an-email')->exists())->toBeFalse();
});
