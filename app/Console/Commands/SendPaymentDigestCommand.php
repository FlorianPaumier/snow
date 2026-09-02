<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SendPaymentDigest;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('payments:send-digest')]
#[Description('Email each organization a weekly digest of installments due soon or overdue')]
final class SendPaymentDigestCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SendPaymentDigest $action): int
    {
        $notified = $action->handle();

        $this->info(sprintf('Digests sent: %d', $notified));

        return self::SUCCESS;
    }
}
