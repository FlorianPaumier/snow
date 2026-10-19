<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Organization;
use App\Models\TourDispatch;
use App\Models\TourTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class DispatchTourTemplates
{
    public function __construct(private CreateTour $create) {}

    /** @param array{request_key: string, scheduled_date: string, template_ids: list<string>} $data */
    public function handle(User $admin, array $data): TourDispatch
    {
        Gate::forUser($admin)->authorize('manage-tours');
        $ids = $data['template_ids'];
        sort($ids);

        return DB::transaction(function () use ($admin, $data, $ids): TourDispatch {
            Organization::query()->whereKey($admin->organization_id)->lockForUpdate()->firstOrFail();
            $existing = TourDispatch::query()->where('organization_id', $admin->organization_id)->where('request_key', $data['request_key'])->first();
            if ($existing !== null) {
                throw_if($existing->template_ids !== $ids || $existing->scheduled_date->toDateString() !== $data['scheduled_date'], ValidationException::withMessages(['request_key' => __('tour_templates.key_reused')]));

                return $existing;
            }

            $templates = TourTemplate::query()->where('organization_id', $admin->organization_id)
                ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            abort_unless($templates->count() === count($ids), 404);
            $dispatch = TourDispatch::query()->create(['organization_id' => $admin->organization_id,
                'request_key' => $data['request_key'], 'scheduled_date' => $data['scheduled_date'], 'template_ids' => $ids]);

            foreach ($templates as $template) {
                throw_if($template->worker_id === null || $template->client_ids === [], ValidationException::withMessages(['template_ids' => __('tour_templates.invalid', ['name' => $template->name])]));
                try {
                    $tour = $this->create->handle($admin, ['name' => $template->name, 'worker_id' => $template->worker_id,
                        'scheduled_date' => $data['scheduled_date'], 'client_ids' => $template->client_ids,
                        'departure_address' => $template->departure_address, 'arrival_address' => $template->arrival_address]);
                } catch (ModelNotFoundException|ValidationException) {
                    throw ValidationException::withMessages(['template_ids' => __('tour_templates.invalid', ['name' => $template->name])]);
                }

                $tour->forceFill(['dispatch_id' => $dispatch->id, 'template_id' => $template->id])->save();
            }

            return $dispatch;
        }, 3);
    }
}
