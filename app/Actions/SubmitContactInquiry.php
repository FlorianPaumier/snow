<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\ContactInquiryMail;
use Illuminate\Support\Facades\Mail;

final readonly class SubmitContactInquiry
{
    /**
     * @param  array<string, ?string>  $attributes
     */
    public function handle(array $attributes): void
    {
        Mail::to(config('mail.from.address'))
            ->send(new ContactInquiryMail($attributes));
    }
}
