<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an action needs a feature its plan does not include. Rendered
 * back to the user as an upgrade prompt instead of a generic error.
 */
final class PlanFeatureLocked extends RuntimeException
{
    //
}
