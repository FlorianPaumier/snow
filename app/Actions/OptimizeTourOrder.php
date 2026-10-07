<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final readonly class OptimizeTourOrder
{
    /**
     * @param  list<array{id: string, longitude: float, latitude: float}>  $stops
     * @param  array{longitude: float, latitude: float}  $start
     * @param  array{longitude: float, latitude: float}  $end
     * @return list<string>
     */
    public function handle(array $stops, array $start, array $end): array
    {
        Validator::make(['stops' => $stops, 'start' => $start, 'end' => $end], [
            'stops' => ['required', 'array', 'list', 'min:1', 'max:'.config()->integer('services.openrouteservice.max_stops')],
            'stops.*' => ['required', 'array:id,longitude,latitude'],
            'stops.*.id' => ['required', 'string', 'distinct:strict'],
            'stops.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'stops.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'start' => ['required', 'array:longitude,latitude'],
            'start.longitude' => ['required', 'numeric', 'between:-180,180'],
            'start.latitude' => ['required', 'numeric', 'between:-90,90'],
            'end' => ['required', 'array:longitude,latitude'],
            'end.longitude' => ['required', 'numeric', 'between:-180,180'],
            'end.latitude' => ['required', 'numeric', 'between:-90,90'],
        ])->validate();

        $key = config()->string('services.openrouteservice.key');

        if (mb_trim($key) === '') {
            throw ValidationException::withMessages(['optimization' => __('tours.optimization_not_configured')]);
        }

        $jobs = [];

        foreach ($stops as $index => $stop) {
            $jobs[] = ['id' => $index + 1, 'location' => [$stop['longitude'], $stop['latitude']]];
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['Authorization' => $key])
                ->timeout(config()->integer('services.openrouteservice.timeout'))
                ->connectTimeout(config()->integer('services.openrouteservice.connect_timeout'))
                ->retry([250, 750], when: fn (Throwable $exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError()))
                ->post(mb_rtrim(config()->string('services.openrouteservice.url'), '/').'/optimization', [
                    'jobs' => $jobs,
                    'vehicles' => [[
                        'id' => 1,
                        'profile' => 'driving-car',
                        'start' => [$start['longitude'], $start['latitude']],
                        'end' => [$end['longitude'], $end['latitude']],
                    ]],
                ])->throw();
        } catch (ConnectionException|RequestException) {
            throw ValidationException::withMessages(['optimization' => __('tours.optimization_unavailable')]);
        }

        $result = $response->json();

        if (! is_array($result) || ($result['code'] ?? null) !== 0
            || ($result['unassigned'] ?? null) !== []
            || ! is_array($result['routes'] ?? null) || count($result['routes']) !== 1) {
            throw new RuntimeException(__('tours.optimization_invalid_response'));
        }

        $route = $result['routes'][0] ?? null;

        if (! is_array($route) || ($route['vehicle'] ?? null) !== 1 || ! is_array($route['steps'] ?? null)) {
            throw new RuntimeException(__('tours.optimization_invalid_response'));
        }

        $order = [];

        foreach ($route['steps'] as $step) {
            if (! is_array($step)) {
                throw new RuntimeException(__('tours.optimization_invalid_response'));
            }

            if (($step['type'] ?? null) !== 'job') {
                continue;
            }

            $id = $step['id'] ?? null;

            if (! is_int($id) || ! isset($stops[$id - 1]) || in_array($stops[$id - 1]['id'], $order, true)) {
                throw new RuntimeException(__('tours.optimization_invalid_response'));
            }

            $order[] = $stops[$id - 1]['id'];
        }

        if (count($order) !== count($stops)) {
            throw new RuntimeException(__('tours.optimization_invalid_response'));
        }

        return $order;
    }
}
