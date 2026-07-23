<?php

namespace App\Http\Controllers;

use App\Enums\VisitStatus;
use App\Http\Requests\StoreVisitDecisionRequest;
use App\Services\VisitDecisionService;
use App\Support\DecisionPageResponse;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class StoreVisitDecisionController extends Controller
{
    public function __invoke(StoreVisitDecisionRequest $request, string $token, VisitDecisionService $decisions): Response
    {
        $requestId = (string) str()->uuid();
        $rateKey = 'decision-action|'.$request->ip().'|'.hash('sha256', $token);

        if (RateLimiter::tooManyAttempts($rateKey, (int) config('api.rate_limits.decision_actions'))) {
            return DecisionPageResponse::make(view('decisions.unavailable'), 429, $requestId);
        }
        RateLimiter::hit($rateKey, 60);

        $decision = VisitStatus::from((string) $request->validated('decision'));
        $visit = $decisions->decide($token, $decision, $request->validated('decision_reason'), $requestId);
        if ($visit === null) {
            return DecisionPageResponse::make(view('decisions.unavailable'), 404, $requestId);
        }

        return DecisionPageResponse::make(view('decisions.result', [
            'accepted' => $visit->status === VisitStatus::Accepted,
        ]), 200, $requestId);
    }
}
