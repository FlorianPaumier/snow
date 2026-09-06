<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an organization invite can no longer be claimed — the code
 * was already used, expired, or lost a race between validation and the
 * claim inside the registration transaction.
 */
final class InviteClaimConflict extends RuntimeException
{
    //
}
