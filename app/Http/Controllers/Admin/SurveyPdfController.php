<?php

namespace App\Http\Controllers\Admin;

use App\Data\SurveyFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SurveyReportRequest;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkUnit;
use App\Services\SurveyReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;

class SurveyPdfController extends Controller
{
    public function __invoke(SurveyReportRequest $request, SurveyReportService $reports): Response
    {
        $filters = SurveyFilters::fromValidated($request->validated());
        $rows = $reports->rows($filters);
        $summary = $reports->summary($filters);
        $employee = $filters->employeeId ? Employee::query()->find($filters->employeeId) : null;
        $workUnit = $filters->workUnitId ? WorkUnit::query()->find($filters->workUnitId) : null;
        $generatedAt = CarbonImmutable::now('Asia/Makassar');
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);

        $pdf = $reports->render(compact('filters', 'rows', 'summary', 'employee', 'workUnit', 'generatedAt'));
        $requestId = (string) str()->uuid();
        AuditLog::query()->create([
            'actor_type' => 'admin', 'action' => 'report.surveys.exported', 'auditable_type' => User::class,
            'auditable_id' => $admin->id,
            'metadata' => [
                'date_from' => $filters->fromDate(), 'date_to' => $filters->toDate(), 'rating' => $filters->rating,
                'employee_id' => $filters->employeeId, 'work_unit_id' => $filters->workUnitId,
                'response_status' => $filters->responseStatus, 'row_count' => $rows->count(),
            ],
            'request_id' => $requestId,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-survei-'.$filters->fromDate().'-sampai-'.$filters->toDate().'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0', 'X-Request-ID' => $requestId,
        ]);
    }
}
