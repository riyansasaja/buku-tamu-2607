<?php

namespace App\Models;

use App\Enums\VisitStatus;
use Database\Factories\VisitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property VisitStatus $status
 * @property Carbon $arrived_at
 * @property Carbon|null $decided_at
 */
#[Fillable([
    'visit_number', 'employee_id', 'guest_name', 'address', 'guest_whatsapp', 'visit_purpose',
    'photo_path', 'photo_mime_type', 'status', 'decision_reason', 'decided_at', 'arrived_at',
    'idempotency_key_hash', 'request_fingerprint',
])]
#[Hidden(['photo_path', 'guest_whatsapp', 'idempotency_key_hash', 'request_fingerprint'])]
class Visit extends Model
{
    /** @use HasFactory<VisitFactory> */
    use HasFactory;

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return HasOne<VisitDecisionToken, $this> */
    public function decisionToken(): HasOne
    {
        return $this->hasOne(VisitDecisionToken::class);
    }

    /** @return HasMany<NotificationDelivery, $this> */
    public function notificationDeliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    /** @return HasOne<SurveyInvitation, $this> */
    public function surveyInvitation(): HasOne
    {
        return $this->hasOne(SurveyInvitation::class);
    }

    /** @return HasOne<SurveyResponse, $this> */
    public function surveyResponse(): HasOne
    {
        return $this->hasOne(SurveyResponse::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => VisitStatus::class,
            'arrived_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
            'guest_whatsapp' => 'encrypted',
        ];
    }
}
