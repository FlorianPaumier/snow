<?php

declare(strict_types=1);

namespace App\Enums;

enum ClientType: string
{
    case CLIENT_INDIVIDUAL = 'individual';
    case CLIENT_COMPANY = 'company';
}
