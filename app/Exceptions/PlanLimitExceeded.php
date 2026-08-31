<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an action would exceed the organization's plan limits. Rendered
 * back to the user as an upgrade prompt instead of a generic error.
 */
final class PlanLimitExceeded extends RuntimeException
{
    //
}
