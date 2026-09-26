<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Manual add / edit of an attendance row by a workspace admin. Access is enforced by
// the route's tenant.admin middleware; this class validates the payload. (It used to
// be the unmodified generator stub — authorize() returned false, so every save 403'd.)
class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;
        $current  = $this->route('attendance'); // bound model on update, null on create

        return [
            'staff_id'  => ['required', 'integer', Rule::exists('staff', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'date'      => [
                'required', 'date',
                // (staff_id, date) is unique in the table — report it instead of 500ing.
                Rule::unique('attendances', 'date')
                    ->where('staff_id', $this->input('staff_id'))
                    ->ignore($current?->getKey()),
            ],
            'status'    => ['required', Rule::in(['present', 'absent', 'half_day', 'holiday', 'leave'])],
            'clock_in'  => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'notes'     => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'staff_id.exists' => 'Selected staff member was not found.',
            'date.unique'     => 'Attendance for this staff member on this date already exists.',
        ];
    }
}
