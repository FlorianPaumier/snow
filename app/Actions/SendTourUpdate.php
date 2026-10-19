<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\SendTourNotification;
use App\Models\Client;
use App\Models\Tour;
use App\Models\TourNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class SendTourUpdate
{
    public function handle(User $admin, Tour $tour, string $event): void
    {
        Gate::forUser($admin)->authorize('manage-tour', $tour);
        DB::transaction(function () use ($tour, $event): void {
            $tour = Tour::query()->withoutGlobalScopes()->where('organization_id', $tour->organization_id)->lockForUpdate()->findOrFail($tour->id);
            throw_unless(in_array($event, ['started', 'finished'], true), ValidationException::withMessages(['event' => __('tour_notifications.invalid_event')]));
            throw_if(($event === 'started' && ($tour->started_at === null || $tour->finished_at !== null)) || ($event === 'finished' && $tour->finished_at === null),
                ValidationException::withMessages(['event' => __('tour_notifications.invalid_event')]));
            $ids = array_values(array_unique(array_column($tour->stops, 'id')));
            $clients = Client::query()->withoutGlobalScopes()->where('organization_id', $tour->organization_id)->whereIn('id', $ids)->get()->keyBy('id');
            $existing = TourNotification::query()->withoutGlobalScopes()->where('organization_id', $tour->organization_id)->where('tour_id', $tour->id)->where('event', $event)->get()->keyBy('client_id');
            $keys = $existing->pluck('recipient_key')->filter()->all();
            foreach ($ids as $id) {
                $previous = $existing->get($id);
                if ($previous !== null) {
                    if ($previous->status === 'pending') {
                        DB::afterCommit(fn () => dispatch(new SendTourNotification($previous->id, $tour->organization_id))->afterCommit());
                    }

                    continue;
                }

                $client = $clients->get($id);
                $reason = match (true) {
                    $client === null => 'missing_client',
                    $client->isAnonymized() => 'anonymized',
                    ! $client->route_notifications_enabled => 'opted_out',
                    ! is_string($client->email) || filter_var($client->email, FILTER_VALIDATE_EMAIL) === false => 'missing_email',
                    default => null,
                };
                $key = $reason === null ? hash('sha256', mb_strtolower(mb_trim((string) $client->email))) : null;
                if ($key !== null && in_array($key, $keys, true)) {
                    $reason = 'duplicate_email';
                    $key = null;
                }

                $notification = TourNotification::query()->create(['organization_id' => $tour->organization_id, 'tour_id' => $tour->id,
                    'client_id' => $id, 'recipient_key' => $key, 'event' => $event, 'locale' => app()->getLocale(),
                    'status' => $reason === null ? 'pending' : 'skipped', 'reason' => $reason]);
                if ($reason === null) {
                    $keys[] = $key;
                    DB::afterCommit(fn () => dispatch(new SendTourNotification($notification->id, $tour->organization_id))->afterCommit());
                }
            }
        });
    }
}
