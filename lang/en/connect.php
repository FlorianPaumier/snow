<?php

declare(strict_types=1);

return [
    'unavailable' => 'Online payment is unavailable. Contact the service provider.',
    'not_ready' => 'The merchant account is not ready to receive payments.',
    'processing' => 'Payment confirmation may take time. Do not send another payment while Stripe is processing it.',
    'locked' => 'This installment is managed through Stripe. Close an unpaid checkout before collecting offline. Manage settled payments in Stripe.',
    'mismatch' => 'Stripe payment details do not match this installment.',
];
