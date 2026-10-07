<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class GeocodeTourAddress
{
    /** @return array{longitude: float, latitude: float} */
    public function handle(string $address, string $organizationId): array
    {
        $key = config()->string('services.openrouteservice.key');

        if (mb_trim($key) === '') {
            throw ValidationException::withMessages(['optimization' => __('tours.optimization_not_configured')]);
        }

        return Cache::remember('tour-geocode:'.$organizationId.':'.hash('sha256', mb_strtolower(mb_trim($address))), now()->addDays(30), function () use ($address, $key): array {
            try {
                $response = Http::acceptJson()->withHeaders(['Authorization' => $key])
                    ->timeout(config()->integer('services.openrouteservice.timeout'))
                    ->connectTimeout(config()->integer('services.openrouteservice.connect_timeout'))
                    ->retry([250, 750], when: fn (Throwable $exception): bool => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && $exception->response->serverError()))
                    ->get(mb_rtrim(config()->string('services.openrouteservice.url'), '/').'/geocode/search', ['text' => $address, 'size' => 1])
                    ->throw();
            } catch (ConnectionException|RequestException) {
                throw ValidationException::withMessages(['optimization' => __('tours.optimization_unavailable')]);
            }

            $feature = $response->json('features.0');
            $geometry = is_array($feature) ? ($feature['geometry'] ?? null) : null;
            $properties = is_array($feature) ? ($feature['properties'] ?? null) : null;
            $coordinates = is_array($geometry) ? ($geometry['coordinates'] ?? null) : null;
            $confidence = is_array($properties) ? ($properties['confidence'] ?? 0) : 0;

            if (! is_array($coordinates) || count($coordinates) !== 2
                || ! is_numeric($coordinates[0]) || ! is_numeric($coordinates[1])
                || abs((float) $coordinates[0]) > 180 || abs((float) $coordinates[1]) > 90
                || ! is_numeric($confidence) || (float) $confidence < 0.8) {
                throw ValidationException::withMessages(['optimization' => __('tours.address_not_found', ['address' => $address])]);
            }

            return ['longitude' => (float) $coordinates[0], 'latitude' => (float) $coordinates[1]];
        });
    }
}
