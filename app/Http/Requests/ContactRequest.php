<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'phone'       => ['required', 'string', 'max:20'],
            'email'       => ['nullable', 'email', 'max:255'],
            'company'     => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'address'     => ['nullable', 'string', 'max:500'],
            'city'        => ['nullable', 'string', 'max:100'],
            'state'       => ['nullable', 'string', 'max:100'],
            'pincode'     => ['nullable', 'string', 'max:10'],
            'gst_number'  => ['nullable', 'string', 'max:20'],
            'notes'       => ['nullable', 'string', 'max:5000'],
            // Must be a live lead in this tenant. A foreign or soft-deleted lead would
            // save but never resolve through the tenant-scoped relation, so the
            // contact page would show "Linked Lead: Not provided".
            'lead_id'     => ['nullable', 'integer', Rule::exists('leads', 'id')
                ->where('tenant_id', $this->user()?->tenant_id)
                ->whereNull('deleted_at')],

            // ── Loyalty engagement ───────────────────────────────────
            'birthday'         => ['nullable', 'date'],
            'anniversary'      => ['nullable', 'date'],
            'referred_by_code' => ['nullable', 'string', 'max:20'],

            // ── Contact-level attachments (visiting card / documents) ──
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx'],

            // ── Company employees ────────────────────────────────────
            'employees'                  => ['nullable', 'array'],
            'employees.*.id'             => ['nullable', 'integer'],
            'employees.*.name'           => ['nullable', 'string', 'max:255'],
            'employees.*.designation'    => ['nullable', 'string', 'max:255'],
            'employees.*.emails'         => ['nullable', 'array'],
            'employees.*.emails.*'       => ['nullable', 'email', 'max:255'],
            'employees.*.phones'         => ['nullable', 'array'],
            'employees.*.phones.*'       => ['nullable', 'string', 'max:20'],
            'employees.*.attachments'    => ['nullable', 'array', 'max:5'],
            'employees.*.attachments.*'  => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx'],
            'removed_employee_ids'       => ['nullable', 'array'],
            'removed_employee_ids.*'     => ['integer'],
            'primary_employee_index'     => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Contact name is required.',
            'phone.required' => 'Phone number is required.',
            'email.email'    => 'Please enter a valid email.',
            'lead_id.exists' => 'Selected lead was not found.',
        ];
    }
}