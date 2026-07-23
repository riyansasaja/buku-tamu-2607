<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Employee */
class EmployeeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_no' => $this->employee_no,
            'name' => $this->name,
            'work_unit' => [
                'id' => $this->workUnit->id,
                'name' => $this->workUnit->name,
            ],
            'position' => [
                'id' => $this->position->id,
                'name' => $this->position->name,
            ],
        ];
    }
}
