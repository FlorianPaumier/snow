<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Domain-specific validation and status messages (English)
|--------------------------------------------------------------------------
*/

return [
    'totals_mismatch' => 'The total of the payments must match the total of the services.',
    'slot_amount_locked' => 'This payment has been collected and its amount can no longer be changed.',
    'duplicate_season' => 'This client already has a contract for this season.',
    'already_marked' => 'This payment is already marked as collected.',
    'not_marked' => 'This payment is not marked as collected.',
    'delete_with_paid' => 'This contract has collected payments and cannot be deleted.',
    'invalid_code' => 'The provided code is invalid or has expired.',
];
