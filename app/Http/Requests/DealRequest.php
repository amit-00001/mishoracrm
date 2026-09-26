<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'               => ['required', 'string', 'max:255'],
            'value'               => ['required', 'numeric', 'min:0'],
            'stage'               => ['required', 'in:new,proposal,negotiation,won,lost'],
            'probability'         => ['nullable', 'integer', 'min:0', 'max:100'],
            'contact_id'          => ['nullable', 'integer', $this->inTenant('contacts')],
            'lead_id'             => ['nullable', 'integer', $this->inTenant('leads')],
            'assigned_to'         => ['nullable', 'integer', Rule::exists('users', 'id')->where('tenant_id', $this->user()?->tenant_id)],
            'expected_close_date' => ['nullable', 'date'],
            'actual_close_date'   => ['nullable', 'date'],
            'notes'               => ['nullable', 'string', 'max:5000'],
            'lost_reason'         => ['nullable', 'string', 'max:500'],
        ];
    }

    // Live (not soft-deleted) row in the current tenant — see ContactRequest::lead_id.
    private function inTenant(string $table): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists($table, 'id')
            ->where('tenant_id', $this->user()?->tenant_id)
            ->whereNull('deleted_at');
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Deal title is required.',
            'value.required' => 'Deal value is required.',
            'stage.required' => 'Deal stage is required.',
            'stage.in'       => 'Invalid stage selected.',
        ];
    }
}