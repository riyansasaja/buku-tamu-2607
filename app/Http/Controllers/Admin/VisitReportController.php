<?php

namespace App\Http\Controllers\Admin;

use App\Data\VisitFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VisitReportRequest;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use App\Services\VisitReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;

class VisitReportController extends Controller
{
    public function __invoke(VisitReportRequest $request, VisitReportService $reports): Response
    {
        $filters = VisitFilters::fromValidated($request->validated(), true);
        $report = $reports->data($filters);
        $employee = $filters->employeeId !== null
            ? Employee::query()->select(['id', 'name'])->find($filters->employeeId)
            : null;
        $generatedAt = CarbonImmutable::now('Asia/Makassar');
        $requestId = (string) str()->uuid();
        $admin = $request->user();

        abort_unless($admin instanceof User, 403);

        $pdf = $reports->render([
            ...$report,
            'filters' => $filters,
            'employee' => $employee,
            'generatedAt' => $generatedAt,
        ]);

        AuditLog::query()->create([
            'actor_type' => 'admin',
            'action' => 'report.visits.exported',
            'auditable_type' => User::class,
            'auditable_id' => $admin->id,
            'metadata' => [
                'date_from' => $filters->fromDate(),
                'date_to' => $filters->toDate(),
                'status' => $filters->status?->value,
                'employee_id' => $filters->employeeId,
                'row_count' => $report['visits']->count(),
            ],
            'request_id' => $requestId,
        ]);

        $filename = sprintf('laporan-kunjungan-%s-sampai-%s.pdf', $filters->fromDate(), $filters->toDate());

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Request-ID' => $requestId,
        ]);
    }
}
