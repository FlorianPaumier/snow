<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\PurgeExpiredContactInquiries;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('contact-inquiries:purge-expired')]
#[Description('Delete contact inquiries beyond the configured retention period')]
final class PurgeExpiredContactInquiriesCommand extends Command
{
    public function handle(PurgeExpiredContactInquiries $action): int
    {
        $deleted = $action->handle();
        $this->components->info(sprintf('Deleted %d expired contact inquiries.', $deleted));

        return self::SUCCESS;
    }
}
