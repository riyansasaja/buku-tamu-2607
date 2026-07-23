<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin Visit */
class VisitResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'visit_number' => $this->visit_number,
            'guest_name' => $this->guest_name,
            'address' => $this->address,
            'visit_purpose' => $this->visit_purpose,
            'status' => $this->status->value,
            'arrived_at' => $this->arrived_at->toIso8601String(),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'photo_url' => URL::temporarySignedRoute(
                'api.v1.visits.photo',
                now()->addMinutes((int) config('api.photo_url_minutes')),
                ['visit' => $this->id],
            ),
        ];
    }
}
