<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\Address;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class AddressCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Address
    {
        return new Address(
            street: $attributes['address_street'],
            city: $attributes['address_city'],
            postalCode: $attributes['address_postal_code'],
            country: $attributes['address_country'],
        );
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        throw_unless($value instanceof Address, InvalidArgumentException::class, 'The given value is not an Address instance.');

        return [
            'address_street' => $value->street,
            'address_city' => $value->city,
            'address_postal_code' => $value->postalCode,
            'address_country' => $value->country,
        ];
    }
}
