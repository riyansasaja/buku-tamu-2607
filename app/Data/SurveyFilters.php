<?php

namespace App\Data;

use Carbon\CarbonImmutable;

readonly class SurveyFilters
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public ?int $rating,
        public ?int $employeeId,
        public ?int $workUnitId,
        public ?string $responseStatus,
        public int $perPage,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        $timezone = 'Asia/Makassar';
        $now = CarbonImmutable::now($timezone);
        $from = is_string($validated['date_from'] ?? null) ? $validated['date_from'] : $now->startOfYear()->format('Y-m-d');
        $to = is_string($validated['date_to'] ?? null) ? $validated['date_to'] : $now->endOfYear()->format('Y-m-d');

        return new self(
            CarbonImmutable::createFromFormat('!Y-m-d', $from, $timezone)->startOfDay(),
            CarbonImmutable::createFromFormat('!Y-m-d', $to, $timezone)->endOfDay(),
            isset($validated['rating']) ? (int) $validated['rating'] : null,
            isset($validated['employee_id']) ? (int) $validated['employee_id'] : null,
            isset($validated['work_unit_id']) ? (int) $validated['work_unit_id'] : null,
            is_string($validated['response_status'] ?? null) ? $validated['response_status'] : null,
            max(1, min(100, (int) ($validated['per_page'] ?? 20))),
        );
    }

    public function fromDate(): string
    {
        return $this->from->format('Y-m-d');
    }

    public function toDate(): string
    {
        return $this->to->format('Y-m-d');
    }

    /** @return array<string, int|string> */
    public function query(): array
    {
        return array_filter([
            'date_from' => $this->fromDate(), 'date_to' => $this->toDate(), 'rating' => $this->rating,
            'employee_id' => $this->employeeId, 'work_unit_id' => $this->workUnitId,
            'response_status' => $this->responseStatus,
        ], fn ($value): bool => $value !== null && $value !== '');
    }
}
