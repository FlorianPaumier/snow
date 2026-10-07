<?php

declare(strict_types=1);

return [
    'optimization_not_configured' => 'Configure the openrouteservice key before optimizing a tour.',
    'optimization_unavailable' => 'Optimization is temporarily unavailable. Try again later; the current order is preserved.',
    'optimization_invalid_response' => 'The service did not return a complete, valid tour.',
    'invalid_clients' => 'The client list is invalid.',
    'cannot_start' => 'This tour cannot be started.',
    'already_active' => 'Finish your active tour before starting another.',
    'cannot_finish' => 'Start the tour before finishing it.',
    'order_locked' => 'The tour has already started or was modified. Refresh the page.',
    'invalid_order' => 'The order must contain exactly the clients assigned to this tour.',
    'address_not_found' => 'Address not found or too imprecise: :address. Correct it before recreating the tour.',
    'departure_required' => 'A departure address is required to optimize the tour.',
    'too_many_stops' => 'This integration supports up to :count clients per optimization. Manual tours remain available.',
];
