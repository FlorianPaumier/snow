<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SendTourUpdate;
use App\Http\Requests\SendTourUpdateRequest;
use App\Models\Tour;
use Illuminate\Http\RedirectResponse;

final readonly class TourNotificationController
{
    public function store(SendTourUpdateRequest $request, Tour $tour, SendTourUpdate $send): RedirectResponse
    {
        $send->handle($request->user() ?? abort(403), $tour, $request->string('event')->toString());

        return to_route('tours.show', $tour)->with('status', __('tour_notifications.queued'));
    }
}
