<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SendQuote;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class SendQuoteController
{
    public function __invoke(Request $request, Quote $quote, SendQuote $send): RedirectResponse
    {
        $send->handle($request->user() ?? abort(403), $quote);

        return to_route('quotes.show', $quote)->with('status', __('quotes.sent_notice'));
    }
}
