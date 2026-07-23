<?php

namespace App\Http\Controllers;

use App\Services\SurveyInvitationService;
use App\Support\DecisionPageResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class SurveyPageController extends Controller
{
    public function __invoke(Request $request, string $token, SurveyInvitationService $surveys): Response
    {
        $requestId = (string) str()->uuid();
        $key = 'survey-page|'.$request->ip().'|'.hash('sha256', $token);
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return DecisionPageResponse::make(view('surveys.unavailable'), 429, $requestId);
        }
        RateLimiter::hit($key, 60);

        if ($surveys->resolve($token) === null) {
            return DecisionPageResponse::make(view('surveys.unavailable'), 404, $requestId);
        }

        return DecisionPageResponse::make(view('surveys.show'), 200, $requestId);
    }
}
