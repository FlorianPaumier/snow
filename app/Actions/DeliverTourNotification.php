<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\TourUpdateMail;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Tour;
use App\Models\TourNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

final readonly class DeliverTourNotification
{
    public function handle(int $notificationId, string $organizationId): void
    {
        $message = DB::transaction(function () use ($notificationId, $organizationId): ?array {
            $notice = TourNotification::query()->withoutGlobalScopes()->where('organization_id', $organizationId)->lockForUpdate()->find($notificationId);
            if ($notice !== null && $notice->status === 'sending') {
                $notice->update(['status' => 'uncertain', 'reason' => 'delivery_unconfirmed']);
            }

            if ($notice === null || $notice->status !== 'pending') {
                return null;
            }

            $client = Client::query()->withoutGlobalScopes()->where('organization_id', $organizationId)->find($notice->client_id);
            $tour = Tour::query()->withoutGlobalScopes()->where('organization_id', $organizationId)->find($notice->tour_id);
            $reason = match (true) {
                $client === null || $tour === null => 'missing_client',
                $client->isAnonymized() => 'anonymized',
                ! $client->route_notifications_enabled => 'opted_out',
                ! is_string($client->email) || filter_var($client->email, FILTER_VALIDATE_EMAIL) === false => 'missing_email',
                hash('sha256', mb_strtolower(mb_trim($client->email))) !== $notice->recipient_key => 'email_changed',
                ! in_array($client->id, array_column($tour->stops, 'id'), true) => 'missing_client',
                $notice->event === 'started' && ($tour->started_at === null || $tour->finished_at !== null) => 'outdated',
                $notice->event === 'finished' && $tour->finished_at === null => 'outdated',
                default => null,
            };
            if ($reason !== null) {
                $notice->update(['status' => 'skipped', 'reason' => $reason, 'recipient_key' => null]);

                return null;
            }

            $organization = Organization::query()->findOrFail($organizationId);
            $notice->update(['status' => 'sending']);

            return ['email' => $client->email, 'organization' => $organization->name, 'event' => $notice->event, 'locale' => $notice->locale,
                'replyTo' => $organization->owner?->email,
                'unsubscribe' => URL::signedRoute('service-updates.unsubscribe', ['organization' => $organizationId, 'client' => $client->id])];
        });
        if ($message === null) {
            return;
        }

        $query = TourNotification::query()->withoutGlobalScopes()->where('organization_id', $organizationId)->whereKey($notificationId);
        try {
            Mail::to($message['email'])->sendNow(new TourUpdateMail($message['organization'], $message['event'], $message['unsubscribe'], $message['replyTo'])->locale($message['locale']));
            $query->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $throwable) {
            // A transport timeout may follow acceptance; automatic resend could email the customer twice.
            $query->update(['status' => 'uncertain', 'reason' => 'delivery_unconfirmed']);
            report($throwable);
        }
    }
}
