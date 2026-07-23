<?php

namespace App\Models;

use App\Enums\SurveyInvitationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property SurveyInvitationStatus $status
 * @property Carbon $scheduled_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $used_at
 * @property Carbon|null $revoked_at
 * @property Visit $visit
 */
#[Fillable(['visit_id', 'token_hash', 'status', 'scheduled_at', 'sent_at', 'used_at', 'revoked_at'])]
#[Hidden(['token_hash'])]
class SurveyInvitation extends Model
{
    /** @return BelongsTo<Visit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /** @return HasOne<SurveyResponse, $this> */
    public function response(): HasOne
    {
        return $this->hasOne(SurveyResponse::class);
    }

    protected function casts(): array
    {
        return [
            'status' => SurveyInvitationStatus::class,
            'scheduled_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'used_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
