<?php

namespace App\Services;

use App\Data\SurveyFilters;
use App\Enums\VisitStatus;
use App\Models\SurveyInvitation;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SurveyReportService
{
    /** @return Builder<SurveyInvitation> */
    public function query(SurveyFilters $filters): Builder
    {
        return SurveyInvitation::query()
            ->whereNotNull('sent_at')
            ->whereHas('visit', function (Builder $query) use ($filters): void {
                $query->where('status', VisitStatus::Accepted)
                    ->whereBetween('arrived_at', [$filters->from, $filters->to])
                    ->when($filters->employeeId !== null, fn (Builder $query) => $query->where('employee_id', $filters->employeeId))
                    ->when($filters->workUnitId !== null, fn (Builder $query) => $query->whereHas('employee', fn (Builder $query) => $query->where('work_unit_id', $filters->workUnitId)));
            })
            ->when($filters->rating !== null, fn (Builder $query) => $query->whereHas('response', fn (Builder $query) => $query->where('rating', $filters->rating)))
            ->when($filters->responseStatus === 'responded', fn (Builder $query) => $query->whereHas('response'))
            ->when($filters->responseStatus === 'not_responded', fn (Builder $query) => $query->whereDoesntHave('response'));
    }

    /** @return array{sent: int, responded: int, not_responded: int, response_rate: float, average_rating: float|null, distribution: array<int, int>} */
    public function summary(SurveyFilters $filters): array
    {
        $row = $this->query($filters)
            ->leftJoin('survey_responses', 'survey_responses.survey_invitation_id', '=', 'survey_invitations.id')
            ->selectRaw('COUNT(*) AS sent, COUNT(survey_responses.id) AS responded, AVG(survey_responses.rating) AS average_rating, '
                .'SUM(CASE WHEN survey_responses.rating = 1 THEN 1 ELSE 0 END) AS rating_1, '
                .'SUM(CASE WHEN survey_responses.rating = 2 THEN 1 ELSE 0 END) AS rating_2, '
                .'SUM(CASE WHEN survey_responses.rating = 3 THEN 1 ELSE 0 END) AS rating_3, '
                .'SUM(CASE WHEN survey_responses.rating = 4 THEN 1 ELSE 0 END) AS rating_4, '
                .'SUM(CASE WHEN survey_responses.rating = 5 THEN 1 ELSE 0 END) AS rating_5')
            ->first();
        $sent = (int) ($row->getAttribute('sent') ?? 0);
        $responded = (int) ($row->getAttribute('responded') ?? 0);

        return [
            'sent' => $sent, 'responded' => $responded, 'not_responded' => $sent - $responded,
            'response_rate' => $sent > 0 ? round(($responded / $sent) * 100, 1) : 0.0,
            'average_rating' => $responded > 0 ? round((float) $row->getAttribute('average_rating'), 2) : null,
            'distribution' => collect(range(1, 5))->mapWithKeys(fn (int $rating): array => [$rating => (int) ($row->getAttribute('rating_'.$rating) ?? 0)])->all(),
        ];
    }

    /** @return Collection<int, SurveyInvitation> */
    public function rows(SurveyFilters $filters): Collection
    {
        return $this->query($filters)->with(['visit.employee.workUnit', 'response'])->latest('sent_at')->get();
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
        $dompdf->loadHtml(view('admin.reports.surveys-pdf', $viewData)->render(), 'UTF-8');
        $dompdf->render();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $dompdf->getCanvas()->page_text(735, 570, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', $font, 7, [0.39, 0.45, 0.55]);

        return $dompdf->output();
    }
}
