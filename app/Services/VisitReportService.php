<?php

namespace App\Services;

use App\Data\VisitFilters;
use App\Models\Visit;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Collection;

class VisitReportService
{
    public function __construct(
        private readonly VisitFilterService $filterService,
        private readonly VisitDashboardService $dashboardService,
    ) {}

    /** @return array{summary: array{total: int, accepted: int, rejected: int, pending: int}, visits: Collection<int, Visit>} */
    public function data(VisitFilters $filters): array
    {
        $visits = $this->filterService->apply(Visit::query(), $filters)
            ->with(['employee.workUnit', 'employee.position'])
            ->latest('arrived_at')
            ->get();

        return [
            'summary' => $this->dashboardService->summary($filters),
            'visits' => $visits,
        ];
    }

    /** @param array<string, mixed> $viewData */
    public function render(array $viewData): string
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->loadHtml(view('admin.reports.visits-pdf', $viewData)->render(), 'UTF-8');
        $dompdf->render();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $dompdf->getCanvas()->page_text(735, 570, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', $font, 7, [0.39, 0.45, 0.55]);

        return $dompdf->output();
    }
}
