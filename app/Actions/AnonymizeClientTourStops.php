<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Client;
use App\Models\Tour;
use Illuminate\Support\Facades\Cache;

final readonly class AnonymizeClientTourStops
{
    public function handle(Client $client): void
    {
        Tour::query()->withoutGlobalScopes()->where('organization_id', $client->organization_id)
            ->lockForUpdate()->lazyById(100)
            ->each(function (Tour $tour) use ($client): void {
                $stops = $tour->stops;

                if (! in_array($client->id, array_column($stops, 'id'), true)) {
                    return;
                }

                foreach ($stops as &$stop) {
                    if ($stop['id'] !== $client->id) {
                        continue;
                    }

                    Cache::forget('tour-geocode:'.$client->organization_id.':'.hash('sha256', mb_strtolower(mb_trim($stop['address']))));
                    $stop['name'] = __('clients.anonymized_name');
                    $stop['address'] = '';
                    $stop['remark'] = null;
                }

                unset($stop);
                $tour->update(['stops' => $stops, 'optimized_at' => null]);
            });
    }
}
