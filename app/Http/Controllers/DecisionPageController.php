<?php

namespace App\Http\Controllers;

use App\Services\DecisionLinkService;
use App\Support\DecisionPageResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class DecisionPageController extends Controller
{
    public function __invoke(Request $request, string $token, DecisionLinkService $links): Response
    {
        $requestId = (string) str()->uuid();
        $rateKey = 'decision-page|'.$request->ip().'|'.hash('sha256', $token);

        if (RateLimiter::tooManyAttempts($rateKey, (int) config('api.rate_limits.decision_pages'))) {
            return DecisionPageResponse::make(view('decisions.unavailable'), 429, $requestId);
        }
        RateLimiter::hit($rateKey, 60);

        $decisionToken = $links->resolve($token);
        if ($decisionToken === null) {
            return DecisionPageResponse::make(view('decisions.unavailable'), 404, $requestId);
        }

        $visit = $decisionToken->visit;
        $photoUrl = URL::temporarySignedRoute(
            'api.v1.visits.photo',
            now()->addMinutes((int) config('api.photo_url_minutes')),
            ['visit' => $visit->id],
        );

        return DecisionPageResponse::make(view('decisions.show', compact('visit', 'photoUrl')), 200, $requestId);
    }
}
