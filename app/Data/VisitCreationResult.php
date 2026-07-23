<?php

namespace App\Data;

use App\Models\Visit;

readonly class VisitCreationResult
{
    public function __construct(public Visit $visit, public bool $replayed) {}
}
