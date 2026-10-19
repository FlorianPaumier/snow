<?php

declare(strict_types=1);

use App\Actions\AnonymizeClient;
use App\Actions\DeliverTourNotification;
use App\Actions\FinishTour;
use App\Actions\SendTourUpdate;
use App\Actions\StartTour;
use App\Jobs\SendTourNotification;
use App\Mail\TourUpdateMail;
use App\Models\Client;
use App\Models\Tour;
use App\Models\TourNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

it('queues one route announcement per opted-in recipient and event after office confirmation', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $this->actingAs($admin)->patch('/clients/'.$client->id.'/route-notifications', ['enabled' => true])->assertRedirect();
    $tour = Tour::factory()->create(['organization_id' => $admin->organization_id, 'started_at' => now(), 'stops' => [['id' => $client->id, 'name' => 'Test', 'address' => 'Test', 'remark' => null]]]);
    Queue::fake();
    $this->post('/tours/'.$tour->id.'/announcements', ['event' => 'started', 'confirmed' => true])->assertRedirect()->assertSessionHasNoErrors();
    $this->post('/tours/'.$tour->id.'/announcements', ['event' => 'started', 'confirmed' => true])->assertRedirect();
    Queue::assertPushed(SendTourNotification::class, 1);
    expect(TourNotification::query()->count())->toBe(1);
});

it('delivers a generic company message once without revealing route or customer details', function (): void {
    $notice = TourNotification::factory()->create();
    $client = Client::query()->findOrFail($notice->client_id);
    $client->forceFill(['route_notifications_enabled' => true])->save();
    $tour = Tour::query()->findOrFail($notice->tour_id);
    $tour->update(['name' => 'CONFIDENTIAL ROUTE ALPHA', 'departure_address' => 'SECRET DEPOT', 'stops' => [['id' => $client->id, 'name' => 'SECRET CUSTOMER', 'address' => 'SECRET ADDRESS', 'remark' => 'SECRET ACCESS']]]);
    Mail::fake();
    $job = new SendTourNotification($notice->id, $notice->organization_id);
    $job->handle(resolve(DeliverTourNotification::class));
    $job->handle(resolve(DeliverTourNotification::class));
    Mail::assertSent(TourUpdateMail::class, 1);
    Mail::assertSent(TourUpdateMail::class, function ($mail) use ($tour, $client): bool {
        $html = $mail->render();
        expect($html)->not->toContain('CONFIDENTIAL', 'SECRET', $tour->id, $client->displayName(), '/tours/', 'ETA', 'cleared');

        return true;
    });
    expect($notice->refresh()->status)->toBe('sent')->and($notice->sent_at)->not->toBeNull();
});

function announcementTour(User $admin, array $clients, array $attributes = []): Tour
{
    return Tour::factory()->create(['organization_id' => $admin->organization_id, 'started_at' => now(),
        'stops' => array_map(fn (Client $client): array => ['id' => $client->id, 'name' => 'Internal name', 'address' => 'Internal address', 'remark' => null], $clients), ...$attributes]);
}

it('respects opt-ins and reports skipped missing anonymized foreign and duplicate recipients', function (): void {
    $admin = User::factory()->admin()->create();
    $yes = Client::factory()->forOrganization($admin->organization)->create(['email' => 'yes@example.com']);
    $duplicate = Client::factory()->forOrganization($admin->organization)->create(['email' => 'yes@example.com']);
    $no = Client::factory()->forOrganization($admin->organization)->create();
    $missing = Client::factory()->forOrganization($admin->organization)->create(['email' => null]);
    $anonymized = Client::factory()->forOrganization($admin->organization)->create(['anonymized_at' => now()]);
    $foreign = Client::factory()->create();
    foreach ([$yes, $duplicate, $missing, $anonymized, $foreign] as $client) {
        $client->forceFill(['route_notifications_enabled' => true])->save();
    }

    $tour = announcementTour($admin, [$yes, $duplicate, $no, $missing, $anonymized, $foreign, $yes]);
    Queue::fake();
    $this->actingAs($admin)->post('/tours/'.$tour->id.'/announcements', ['event' => 'started', 'confirmed' => true])->assertRedirect();
    Queue::assertPushed(SendTourNotification::class, 1);
    expect(TourNotification::query()->count())->toBe(6)
        ->and(TourNotification::query()->where('status', 'skipped')->pluck('reason')->sort()->values()->all())
        ->toBe(['anonymized', 'duplicate_email', 'missing_client', 'missing_email', 'opted_out']);
});

it('requires an admin explicit confirmation a matching lifecycle event and tenant ownership', function (): void {
    $admin = User::factory()->admin()->create();
    $tour = announcementTour($admin, [], ['started_at' => null]);
    Queue::fake();
    $this->actingAs($admin)->post('/tours/'.$tour->id.'/announcements', ['event' => 'started', 'confirmed' => true])->assertSessionHasErrors('event');
    $this->post('/tours/'.$tour->id.'/announcements', ['event' => 'finished', 'confirmed' => true])->assertSessionHasErrors('event');
    $this->post('/tours/'.$tour->id.'/announcements', ['event' => 'started'])->assertSessionHasErrors('confirmed');
    $this->post('/tours/'.$tour->id.'/announcements', ['event' => 'property_cleared', 'confirmed' => true])->assertSessionHasErrors('event');
    $worker = User::factory()->worker()->forOrganization($admin->organization)->create();
    $this->actingAs($worker)->post('/tours/'.$tour->id.'/announcements', ['event' => 'started', 'confirmed' => true])->assertForbidden();
    $this->patch('/clients/'.Client::factory()->forOrganization($admin->organization)->create()->id.'/route-notifications', ['enabled' => true])->assertForbidden();
    $other = User::factory()->admin()->create();
    $this->actingAs($other)->post('/tours/'.$tour->id.'/announcements', ['event' => 'started', 'confirmed' => true])->assertNotFound();
    Queue::assertNothingPushed();
});

it('sends nothing when the surrounding database transaction rolls back', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $client->forceFill(['route_notifications_enabled' => true])->save();
    $tour = announcementTour($admin, [$client]);
    config(['queue.default' => 'database']);
    try {
        DB::transaction(function () use ($admin, $tour): void {
            resolve(SendTourUpdate::class)->handle($admin, $tour, 'started');
            expect(DB::table('jobs')->count())->toBe(0);
            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException $runtimeException) {
        expect($runtimeException->getMessage())->toBe('rollback');
    }

    expect(TourNotification::query()->count())->toBe(0)->and(DB::table('jobs')->count())->toBe(0);
});

it('rechecks the recipient and lifecycle immediately before queued delivery', function (string $change, string $reason): void {
    $notice = TourNotification::factory()->create();
    $client = Client::query()->findOrFail($notice->client_id);
    $client->forceFill(['route_notifications_enabled' => true])->save();
    match ($change) {
        'optout' => $client->forceFill(['route_notifications_enabled' => false])->save(),
        'anonymize' => $client->forceFill(['anonymized_at' => now()])->save(),
        'email' => $client->update(['email' => 'changed@example.com']),
        'finish' => Tour::query()->whereKey($notice->tour_id)->update(['finished_at' => now()]),
        'delete' => $client->delete(),
    };
    Mail::fake();
    new SendTourNotification($notice->id, $notice->organization_id)->handle(resolve(DeliverTourNotification::class));
    Mail::assertNothingSent();
    expect($notice->refresh()->status)->toBe('skipped')->and($notice->reason)->toBe($reason)->and($notice->recipient_key)->toBeNull();
})->with([['optout', 'opted_out'], ['anonymize', 'anonymized'], ['email', 'email_changed'], ['finish', 'outdated'], ['delete', 'missing_client']]);

it('does not resend after an ambiguous mail transport failure or a crashed delivery', function (): void {
    $notice = TourNotification::factory()->create();
    Client::query()->findOrFail($notice->client_id)->forceFill(['route_notifications_enabled' => true])->save();
    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Transport timeout'));
    $job = new SendTourNotification($notice->id, $notice->organization_id);
    $job->handle(resolve(DeliverTourNotification::class));
    $job->handle(resolve(DeliverTourNotification::class));

    expect($notice->refresh()->status)->toBe('uncertain');
    $notice->update(['status' => 'sending']);
    $job->failed(new RuntimeException('Worker timed out'));
    expect($notice->refresh()->status)->toBe('uncertain');

    $notice->update(['status' => 'pending']);
    $job->failed(null);
    expect($notice->refresh()->status)->toBe('uncertain')->and($notice->reason)->toBe('delivery_unconfirmed');
});

it('keeps queued jobs inside their owning organization', function (): void {
    $notice = TourNotification::factory()->create();
    $other = User::factory()->admin()->create();
    Mail::fake();
    new SendTourNotification($notice->id, $other->organization_id)->handle(resolve(DeliverTourNotification::class));
    Mail::assertNothingSent();
    expect($notice->refresh()->status)->toBe('pending');
});

it('provides a signed opt-out with a read-only confirmation page and no route details', function (): void {
    $client = Client::factory()->create();
    $client->forceFill(['route_notifications_enabled' => true])->save();
    $url = URL::signedRoute('service-updates.unsubscribe', ['organization' => $client->organization_id, 'client' => $client->id]);
    $other = User::factory()->admin()->create();
    $this->actingAs($other)->get($url)->assertOk();
    expect($client->refresh()->route_notifications_enabled)->toBeTrue();
    $this->post($url)->assertRedirect($url);
    expect($client->refresh()->route_notifications_enabled)->toBeFalse();
    $this->post('/service-updates/'.$client->organization_id.'/'.$client->id.'/unsubscribe')->assertForbidden();
    $this->get(str_replace($client->id, $other->id, $url))->assertForbidden();
});

it('removes notification contact fingerprints when a client is anonymized', function (): void {
    $notice = TourNotification::factory()->create();
    $client = Client::query()->findOrFail($notice->client_id);
    $client->forceFill(['route_notifications_enabled' => true])->save();
    resolve(AnonymizeClient::class)->handle($client);
    expect($notice->refresh()->recipient_key)->toBeNull()->and($client->refresh()->route_notifications_enabled)->toBeFalse();
});

it('lets the office recover a pending notification whose job was never queued', function (): void {
    $notice = TourNotification::factory()->create();
    $client = Client::query()->findOrFail($notice->client_id);
    $client->forceFill(['route_notifications_enabled' => true])->save();
    $admin = User::factory()->admin()->forOrganization($client->organization)->create();
    Queue::fake();
    $this->actingAs($admin)->post('/tours/'.$notice->tour_id.'/announcements', ['event' => 'started', 'confirmed' => true])->assertRedirect();
    Queue::assertPushed(SendTourNotification::class, 1);
    expect(TourNotification::query()->count())->toBe(1);
});

it('uses separate started and finished deliveries and never announces a property completion', function (): void {
    $admin = User::factory()->admin()->create();
    $client = Client::factory()->forOrganization($admin->organization)->create();
    $client->forceFill(['route_notifications_enabled' => true])->save();
    $tour = announcementTour($admin, [$client]);
    Queue::fake();
    $this->actingAs($admin)->post('/tours/'.$tour->id.'/announcements', ['event' => 'started', 'confirmed' => true])->assertRedirect();
    $tour->update(['finished_at' => now()]);
    $this->post('/tours/'.$tour->id.'/announcements', ['event' => 'finished', 'confirmed' => true])->assertRedirect();
    Queue::assertPushed(SendTourNotification::class, 2);
    Mail::fake();
    $notice = TourNotification::query()->where('event', 'finished')->sole();
    new SendTourNotification($notice->id, $notice->organization_id)->handle(resolve(DeliverTourNotification::class));
    Mail::assertSent(TourUpdateMail::class, function ($mail): bool {
        expect($mail->render())->toContain('a terminé une opération')->not->toContain('/tours/');

        return true;
    });
});

it('marks an interrupted send for office review when the worker retries without sending again', function (): void {
    $notice = TourNotification::factory()->create(['status' => 'sending']);
    Mail::fake();
    new SendTourNotification($notice->id, $notice->organization_id)->handle(resolve(DeliverTourNotification::class));
    Mail::assertNothingSent();
    expect($notice->refresh()->status)->toBe('uncertain')->and($notice->reason)->toBe('delivery_unconfirmed');
});

it('adds no automatic customer messages to the worker start or finish actions', function (): void {
    $worker = User::factory()->worker()->create();
    $tour = Tour::factory()->create(['organization_id' => $worker->organization_id, 'worker_id' => $worker->id, 'scheduled_date' => today()]);
    Queue::fake();
    $this->actingAs($worker);
    resolve(StartTour::class)->handle($worker, $tour);
    resolve(FinishTour::class)->handle($worker, $tour->refresh());
    Queue::assertNothingPushed();
    expect(TourNotification::query()->count())->toBe(0);
});
