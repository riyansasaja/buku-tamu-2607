<?php

namespace App\Http\Controllers;

use App\Support\HealthStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
        } catch (Throwable) {
            return response()->json(
                HealthStatus::databaseUnavailable(),
                JsonResponse::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        return response()->json(HealthStatus::healthy());
    }
}
