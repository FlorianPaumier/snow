<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Client;
use Illuminate\Validation\ValidationException;

final readonly class DeleteClient
{
    public function handle(Client $client): void
    {
        throw_if(
            $client->contracts()->exists(),
            ValidationException::withMessages([
                'client' => 'This client has contracts and cannot be deleted.',
            ]),
        );

        $client->delete();
    }
}
