<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case ROLE_ADMIN = 'admin';

    case ROLE_WORKER = 'worker';

    case ROLE_USER = 'user';
}
