<?php

declare(strict_types=1);

use App\Jobs\SendContactInquiry;
use App\Models\ContactInquiry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

it('renders all public marketing pages', function (string $path, string $component): void {
    $this->get($path)->assertOk()->assertInertia(
        fn ($page) => $page->component($component),
    );
})->with([
    '/' => ['/', 'marketing/home'],
    'features' => ['/features', 'marketing/features'],
    'pricing' => ['/pricing', 'marketing/pricing'],
    'about' => ['/about', 'marketing/about'],
    'contact' => ['/contact', 'marketing/contact'],
    'terms' => ['/terms', 'marketing/terms'],
    'privacy' => ['/privacy', 'marketing/privacy'],
    'status' => ['/status', 'marketing/status'],
]);

it('reports live component health on the status page', function (): void {
    $this->get('/status')->assertOk()->assertInertia(
        fn ($page) => $page
            ->component('marketing/status')
            ->has('components', 3)
            ->where('components.0.name', 'Database')
            ->where('components.0.status', 'operational')
            ->where('components.1.name', 'Cache store')
            ->where('components.1.status', 'operational')
            ->where('components.2.name', 'Job queue')
            ->where('components.2.status', 'operational'),
    );
});

it('publishes a crawlable sitemap for public pages', function (): void {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<loc>http://localhost/</loc>', false)
        ->assertSee('<loc>http://localhost/features</loc>', false)
        ->assertDontSee('/dashboard', false);
});

it('degrades a failing component instead of failing the status page', function (): void {
    // A cache store that explodes keeps its probe row red while the page
    // itself stays up — the probes must never take the page down with
    // them...
    Cache::shouldReceive('store')->andThrow(new RuntimeException('cache down'));

    $this->get('/status')->assertOk()->assertInertia(
        fn ($page) => $page
            ->component('marketing/status')
            ->where('components.0.status', 'operational')
            ->where('components.1.name', 'Cache store')
            ->where('components.1.status', 'degraded')
            ->where('components.2.status', 'operational'),
    );
});

it('persists a contact inquiry and queues its delivery', function (): void {
    Queue::fake();

    $response = $this->postJson('/contact', [
        'name' => 'Dana Kowalski',
        'email' => 'dana@example.com',
        'company' => 'Twin Cities Snow Co.',
        'fleetSize' => '4-10',
        'message' => 'We run six trucks across the metro and need dispatch.',
    ]);

    $response->assertOk()->assertJson(['ok' => true]);

    $inquiry = ContactInquiry::query()->sole();

    $this->assertDatabaseHas(ContactInquiry::class, [
        'name' => 'Dana Kowalski',
        'email' => 'dana@example.com',
        'company' => 'Twin Cities Snow Co.',
        'fleetSize' => '4-10',
        'message' => 'We run six trucks across the metro and need dispatch.',
    ]);

    Queue::assertPushed(
        SendContactInquiry::class,
        fn (SendContactInquiry $job): bool => $job->inquiryId === $inquiry->id,
    );
});

it('validates the contact inquiry payload', function (): void {
    Queue::fake();

    $response = $this->postJson('/contact', [
        'name' => 'D',
        'email' => 'not-an-email',
        'message' => 'short',
    ]);

    $response->assertUnprocessable()->assertInvalid(['name', 'email', 'message']);

    Queue::assertNothingPushed();
});

it('validates the contact inquiry bounds and enum fields', function (array $payload, array $expectedErrors): void {
    Queue::fake();

    $base = [
        'name' => 'Dana Kowalski',
        'email' => 'dana@example.com',
        'message' => 'We run six trucks across the metro and need dispatch.',
    ];

    $response = $this->postJson('/contact', [...$base, ...$payload]);

    $response->assertUnprocessable()->assertInvalid($expectedErrors);

    Queue::assertNothingPushed();
})->with([
    'unknown fleet size' => [['fleetSize' => '15-30'], ['fleetSize']],
    'company over 255 characters' => [['company' => str_repeat('a', 256)], ['company']],
    'name over 255 characters' => [['name' => str_repeat('a', 256)], ['name']],
    'message over 5000 characters' => [['message' => str_repeat('a', 5001)], ['message']],
]);

it('accepts every documented fleet size option', function (string $fleetSize): void {
    Queue::fake();

    $response = $this->postJson('/contact', [
        'name' => 'Dana Kowalski',
        'email' => 'dana@example.com',
        'fleetSize' => $fleetSize,
        'message' => 'We run six trucks across the metro and need dispatch.',
    ]);

    $response->assertOk()->assertJson(['ok' => true]);

    $this->assertDatabaseHas(ContactInquiry::class, ['fleetSize' => $fleetSize]);

    Queue::assertPushed(SendContactInquiry::class, 1);
})->with([
    '1-3' => ['1-3'],
    '4-10' => ['4-10'],
    '11-50' => ['11-50'],
    '50+' => ['50+'],
]);
