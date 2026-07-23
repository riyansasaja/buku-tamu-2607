<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyResponseRequest;
use App\Services\SurveyInvitationService;
use App\Support\DecisionPageResponse;
use Symfony\Component\HttpFoundation\Response;

class StoreSurveyResponseController extends Controller
{
    public function __invoke(StoreSurveyResponseRequest $request, string $token, SurveyInvitationService $surveys): Response
    {
        $requestId = (string) str()->uuid();
        $saved = $surveys->submit($token, (int) $request->validated('rating'), $request->validated('comment'));

        return $saved
            ? DecisionPageResponse::make(view('surveys.complete'), 200, $requestId)
            : DecisionPageResponse::make(view('surveys.unavailable'), 404, $requestId);
    }
}
