<?php

namespace App\Http\Requests\Admin;

class VisitReportRequest extends VisitFilterRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_intersect_key(parent::rules(), array_flip([
            'date_from',
            'date_to',
            'status',
            'employee_id',
        ]));
    }
}
