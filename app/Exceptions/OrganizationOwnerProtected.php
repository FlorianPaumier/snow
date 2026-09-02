<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an action would delete or demote the organization's owner.
 * Ownership must be transferred first; rendered back to the user as a
 * notice instead of a generic error.
 */
final class OrganizationOwnerProtected extends RuntimeException
{
    //
}
