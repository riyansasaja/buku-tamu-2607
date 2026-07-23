<?php

namespace App\Data;

use App\Models\VisitDecisionToken;

readonly class IssuedDecisionLink
{
    public function __construct(
        public VisitDecisionToken $decisionToken,
        public string $plainToken,
        public string $url,
    ) {}
}
