<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SendAutomatedPaymentReminders;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('payments:send-reminders')]
#[Description('Send automated payment reminders for installments that are due soon or overdue')]
final class SendPaymentRemindersCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SendAutomatedPaymentReminders $action): int
    {
        $sent = $action->handle();

        $this->info(sprintf('Automated reminders sent: %d', $sent));

        return self::SUCCESS;
    }
}
