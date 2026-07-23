<?php

namespace App\Data;

use App\Enums\VisitStatus;
use Carbon\CarbonImmutable;

readonly class VisitFilters
{
    public function __construct(
        public ?CarbonImmutable $from,
        public ?CarbonImmutable $to,
        public ?VisitStatus $status,
        public ?int $employeeId,
        public ?string $search,
        public int $perPage,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return self::make($validated);
    }

    /** @param array<string, mixed> $validated */
    public static function fromValidatedForToday(array $validated): self
    {
        $today = CarbonImmutable::now('Asia/Makassar')->format('Y-m-d');

        return self::make($validated, $today, $today);
    }

    /** @param array<string, mixed> $validated */
    public static function fromValidatedForCurrentYear(array $validated): self
    {
        $now = CarbonImmutable::now('Asia/Makassar');

        return self::make($validated, $now->startOfYear()->format('Y-m-d'), $now->endOfYear()->format('Y-m-d'));
    }

    /** @param array<string, mixed> $validated */
    private static function make(array $validated, ?string $defaultFrom = null, ?string $defaultTo = null): self
    {
        $timezone = 'Asia/Makassar';
        $hasExplicitPeriod = isset($validated['date_from']) || isset($validated['date_to']);
        $fromDate = $validated['date_from'] ?? ($hasExplicitPeriod ? null : $defaultFrom);
        $toDate = $validated['date_to'] ?? ($hasExplicitPeriod ? null : $defaultTo);
        $search = isset($validated['q']) ? trim((string) $validated['q']) : null;

        return new self(
            is_string($fromDate) ? CarbonImmutable::createFromFormat('!Y-m-d', $fromDate, $timezone)->startOfDay() : null,
            is_string($toDate) ? CarbonImmutable::createFromFormat('!Y-m-d', $toDate, $timezone)->endOfDay() : null,
            isset($validated['status']) ? VisitStatus::tryFrom((string) $validated['status']) : null,
            isset($validated['employee_id']) ? (int) $validated['employee_id'] : null,
            $search !== null && $search !== '' ? $search : null,
            max(1, min(100, (int) ($validated['per_page'] ?? 20))),
        );
    }

    public function fromDate(): ?string
    {
        return $this->from?->format('Y-m-d');
    }

    public function toDate(): ?string
    {
        return $this->to?->format('Y-m-d');
    }
}
