<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SubmitContactInquiry;
use App\Http\Requests\StoreContactInquiryRequest;
use Illuminate\Http\JsonResponse;

final readonly class ContactInquiryController
{
    public function store(StoreContactInquiryRequest $request, SubmitContactInquiry $action): JsonResponse
    {
        $action->handle([
            'name' => $request->string('name')->trim()->value(),
            'email' => $request->string('email')->trim()->value(),
            'company' => $request->string('company')->trim()->value() ?: null,
            'fleetSize' => $request->string('fleetSize')->value() ?: null,
            'message' => $request->string('message')->trim()->value(),
        ]);

        return response()->json(['ok' => true]);
    }
}
