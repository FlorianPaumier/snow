<?php

declare(strict_types=1);

use App\Actions\OptimizeTourOrder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    config(['services.openrouteservice.key' => 'test-key']);
    $this->stops = [
        ['id' => 'client-a', 'longitude' => -73.57, 'latitude' => 45.50],
        ['id' => 'client-b', 'longitude' => -73.60, 'latitude' => 45.52],
    ];
    $this->depot = ['longitude' => -73.56, 'latitude' => 45.49];
    $this->result = [
        'code' => 0,
        'unassigned' => [],
        'routes' => [['vehicle' => 1, 'steps' => [
            ['type' => 'start'],
            ['type' => 'job', 'id' => 2],
            ['type' => 'job', 'id' => 1],
            ['type' => 'end'],
        ]]],
    ];
});

it('returns client ids in road optimized order without sending identities', function (): void {
    Http::fake(['*/optimization' => Http::response($this->result)]);

    expect(resolve(OptimizeTourOrder::class)->handle($this->stops, $this->depot, $this->depot))
        ->toBe(['client-b', 'client-a']);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openrouteservice.org/optimization'
        && $request->hasHeader('Authorization', 'test-key')
        && $request->data() === [
            'jobs' => [
                ['id' => 1, 'location' => [-73.57, 45.50]],
                ['id' => 2, 'location' => [-73.60, 45.52]],
            ],
            'vehicles' => [['id' => 1, 'profile' => 'driving-car', 'start' => [-73.56, 45.49], 'end' => [-73.56, 45.49]]],
        ]);
});

it('requires a configured key before making requests', function (): void {
    config(['services.openrouteservice.key' => '']);

    try {
        resolve(OptimizeTourOrder::class)->handle($this->stops, $this->depot, $this->depot);
    } finally {
        Http::assertNothingSent();
    }
})->throws(ValidationException::class);

it('rejects invalid stops before making requests', function (array $stops): void {
    try {
        resolve(OptimizeTourOrder::class)->handle($stops, $this->depot, $this->depot);
    } finally {
        Http::assertNothingSent();
    }
})->with([
    'empty' => [[]],
    'invalid longitude' => [[['id' => 'a', 'longitude' => 181, 'latitude' => 45]]],
    'invalid latitude' => [[['id' => 'a', 'longitude' => -73, 'latitude' => 91]]],
    'duplicate ids' => [[
        ['id' => 'a', 'longitude' => -73, 'latitude' => 45],
        ['id' => 'a', 'longitude' => -74, 'latitude' => 46],
    ]],
    'too many clients' => [array_map(fn (int $id): array => ['id' => (string) $id, 'longitude' => -73, 'latitude' => 45], range(1, 51))],
])->throws(ValidationException::class);

it('does not retry quota or authentication failures', function (int $status): void {
    Http::fake(['*/optimization' => Http::response(['error' => 'failure'], $status)]);

    try {
        resolve(OptimizeTourOrder::class)->handle($this->stops, $this->depot, $this->depot);
    } finally {
        Http::assertSentCount(1);
    }
})->with([401, 403, 429])->throws(ValidationException::class);

it('retries transient server failures', function (): void {
    Http::fake(['*/optimization' => Http::sequence()->push([], 503)->push($this->result)]);

    expect(resolve(OptimizeTourOrder::class)->handle($this->stops, $this->depot, $this->depot))->toBe(['client-b', 'client-a']);
    Http::assertSentCount(2);
});

it('handles connection failures with a user facing error', function (): void {
    Http::fake(['*/optimization' => Http::failedConnection()]);

    resolve(OptimizeTourOrder::class)->handle($this->stops, $this->depot, $this->depot);
})->throws(ValidationException::class);

it('rejects incomplete or malformed routes', function (array $result): void {
    Http::fake(['*/optimization' => Http::response($result)]);

    resolve(OptimizeTourOrder::class)->handle($this->stops, $this->depot, $this->depot);
})->with([
    'empty response' => [[]],
    'invalid vehicle' => [['code' => 0, 'unassigned' => [], 'routes' => [['vehicle' => 2, 'steps' => []]]]],
    'invalid step' => [['code' => 0, 'unassigned' => [], 'routes' => [['vehicle' => 1, 'steps' => ['invalid']]]]],
    'unassigned client' => [['code' => 0, 'unassigned' => [['id' => 2]], 'routes' => []]],
    'duplicate stop' => [['code' => 0, 'unassigned' => [], 'routes' => [['vehicle' => 1, 'steps' => [['type' => 'job', 'id' => 1], ['type' => 'job', 'id' => 1]]]]]],
    'missing stop' => [['code' => 0, 'unassigned' => [], 'routes' => [['vehicle' => 1, 'steps' => [['type' => 'job', 'id' => 1]]]]]],
    'unknown stop' => [['code' => 0, 'unassigned' => [], 'routes' => [['vehicle' => 1, 'steps' => [['type' => 'job', 'id' => 99]]]]]],
])->throws(RuntimeException::class);
