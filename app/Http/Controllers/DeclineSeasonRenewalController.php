<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeclineSeasonRenewal;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class DeclineSeasonRenewalController
{
    public function __invoke(Request $request, Quote $quote, DeclineSeasonRenewal $decline): RedirectResponse
    {
        $decline->handle($request->user() ?? abort(403), $quote);

        return to_route('quotes.show', $quote);
    }
}
