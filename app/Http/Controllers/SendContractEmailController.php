<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\QueueContractEmails;
use App\Http\Requests\SendContractEmailRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class SendContractEmailController
{
    public function __invoke(SendContractEmailRequest $request, #[CurrentUser] User $user, QueueContractEmails $queue): RedirectResponse
    {
        $count = $queue->handle($user, $request->contractIds());

        return back()->with('status', $count > 0 ? __('contract_mail.queued', ['count' => $count]) : __('contract_mail.already_queued'));
    }
}
