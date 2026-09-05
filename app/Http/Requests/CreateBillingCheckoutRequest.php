<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateBillingCheckoutRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'plan' => ['required', 'string', Rule::in($this->paidPlans())],
            // Snow removal only bills for part of the year, so Pro is sold
            // as a single annual payment — there is no monthly interval...
            'interval' => ['required', 'string', Rule::in(['annual'])],
        ];
    }

    /**
     * Only plans with a Stripe price can be checked out; the Free plan is
     * the default state, not something you subscribe to...
     *
     * @return list<string>
     */
    private function paidPlans(): array
    {
        /** @var array<string, array{name?: string, prices?: array<string, string>}> $plans */
        $plans = config('plans');

        $paidPlans = [];

        foreach ($plans as $key => $plan) {
            if (array_key_exists('prices', $plan)) {
                $paidPlans[] = $key;
            }
        }

        return $paidPlans;
    }
}
