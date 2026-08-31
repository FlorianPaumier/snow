<?php

declare(strict_types=1);

use App\Actions\DeleteClient;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

// The organization scope requires an authenticated user to see any data;
// records must belong to that user's organization to be visible...
beforeEach(function (): void {
    $this->user = User::factory()->create();

    Auth::login($this->user);
});

it('deletes a client without contracts', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    $action = resolve(DeleteClient::class);

    $action->handle($client);

    expect($client->fresh())->toBeNull();
});

it('refuses to delete a client that has contracts', function (): void {
    $client = Client::factory()->forOrganization($this->user->organization)->create();
    Contract::factory()->forClient($client)->create();
    $action = resolve(DeleteClient::class);

    try {
        $action->handle($client);
        $this->fail('The deletion should have been refused.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('client')
            ->and($client->fresh())->not->toBeNull();
    }
});
