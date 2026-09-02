<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactInquiryStatus: string
{
    case STATUS_PENDING = 'pending';

    case STATUS_SENT = 'sent';

    case STATUS_FAILED = 'failed';

    public function isSent(): bool
    {
        return $this === self::STATUS_SENT;
    }
}
