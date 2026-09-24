<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ContactInquiry;

final readonly class PurgeExpiredContactInquiries
{
    public function handle(): int
    {
        $cutoff = now()->subMonths(config()->integer('privacy.contact_inquiry_retention_months'));

        return ContactInquiry::query()
            ->where('created_at', '<', $cutoff)
            ->toBase()
            ->delete();
    }
}
