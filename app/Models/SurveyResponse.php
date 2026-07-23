<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['survey_invitation_id', 'visit_id', 'rating', 'comment', 'submitted_at'])]
class SurveyResponse extends Model
{
    /** @return BelongsTo<SurveyInvitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(SurveyInvitation::class, 'survey_invitation_id');
    }

    /** @return BelongsTo<Visit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    protected function casts(): array
    {
        return ['rating' => 'integer', 'submitted_at' => 'immutable_datetime'];
    }
}
