<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreVisitRequest;
use App\Http\Resources\Api\V1\VisitResource;
use App\Services\VisitCreationService;
use Illuminate\Http\JsonResponse;

class VisitController extends Controller
{
    public function store(StoreVisitRequest $request, VisitCreationService $service): JsonResponse
    {
        $validated = $request->validated();
        $result = $service->create($validated, $request->file('photo'));
        $result->visit->load(['employee.workUnit', 'employee.position']);

        return response()->json([
            'data' => new VisitResource($result->visit),
            'meta' => [
                'request_id' => $request->attributes->get('request_id'),
                'idempotency_replayed' => $result->replayed,
            ],
        ], $result->replayed ? 200 : 201)->header('Idempotency-Replayed', $result->replayed ? 'true' : 'false');
    }
}
