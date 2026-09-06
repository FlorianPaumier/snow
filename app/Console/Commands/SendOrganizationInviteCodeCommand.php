<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SendOrganizationInviteCode;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('organization:send-invite-code {email}')]
#[Description('Email a single-use invite code that lets the recipient create an organization')]
final class SendOrganizationInviteCodeCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SendOrganizationInviteCode $action): int
    {
        $email = $this->argument('email');

        $action->handle($email);

        $this->info(sprintf('Invite code sent to %s.', $email));

        return self::SUCCESS;
    }
}
