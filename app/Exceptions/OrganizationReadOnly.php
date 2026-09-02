<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a read-only organization (expired trial, no valid
 * subscription, no active unlock) attempts a mutating request. Rendered
 * back to the user as an upgrade prompt instead of a generic error.
 */
final class OrganizationReadOnly extends RuntimeException
{
    //
}
