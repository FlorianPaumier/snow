<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\OrganizationInviteCodeMail;
use Illuminate\Support\Facades\Mail;

final readonly class SendOrganizationInviteCode
{
    public function __construct(private GenerateOrganizationInviteCode $generateCode) {}

    /**
     * Issue an invite code for the prospect's email and deliver it.
     */
    public function handle(string $email): string
    {
        $code = $this->generateCode->handle($email);

        Mail::to(mb_strtolower($email))->send(
            new OrganizationInviteCodeMail(
                $code,
                now()->addDays(GenerateOrganizationInviteCode::LIFETIME_DAYS)->toDateString(),
            ),
        );

        return $code;
    }
}
