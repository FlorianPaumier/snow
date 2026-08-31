<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class Address
{
    public function __construct(
        public string $street,
        public string $city,
        public string $postalCode,
        public string $country,
    ) {}
}
