<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Lead;
use App\Models\TenantFieldAssignment;

class LeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
       return array_merge(
            $this->coreRules(),
            $this->customFieldRules()
        );
    }

    public function messages(): array
    {
        return array_merge([
            'name.required'          => 'Lead name is required.',
            'phone.required'         => 'Phone number is required.',
            'email.email'            => 'Please enter a valid email address.',
            'source.in'              => 'Invalid lead source selected.',
            'status.in'              => 'Invalid status selected.',
            'priority.in'            => 'Priority must be low, medium or high.',
            'lead_value.numeric'     => 'Lead value must be a number.',
            'assigned_to.exists'     => 'Selected staff member does not exist.',
            'expected_close_date.after' => 'Expected close date must be a future date.',
        ],
        $this->customFieldMessages()
        );
    }


    public function attributes(): array
    {
        return array_merge(
            [
                'name'        => 'Name',
                'phone'       => 'Phone',
                'email'       => 'Email',
                'source'      => 'Source',
                'status'      => 'Status',
                'priority'    => 'Priority',
                'assigned_to' => 'Assigned To',
            ],
            $this->customFieldAttributes()
        );
    }
    public function leadData(): array
    {
        return [
            // Core fields
            'name'                 => $this->name,
            'phone'                => $this->phone,
            'email'                => $this->email,
            'company'              => $this->company,
            'designation'          => $this->designation,
            'city'                 => $this->city,
            'state'                => $this->state,
            'source'               => $this->source,
            'status'               => $this->status,
            'priority'             => $this->priority,
            'lead_value'           => $this->lead_value,
            'assigned_to'          => $this->assigned_to,
            'notes'                => $this->notes,
            // 'lost_reason'          => $this->lost_reason,
            'expected_close_date'  => $this->expected_close_date,

            // Future mein yahan add karo — controller nahi badlega
            // 'company'     => $this->company,
            // 'address'     => $this->address,
            // 'tags'        => $this->tags,
        ];
    }

    /**
     * Only the core fields that were actually submitted. Use this for updates:
     * a key that is absent from the request (e.g. the edit form has no company /
     * city / value inputs) must keep its stored value, whereas a key that is
     * present but empty is an intentional clear. leadData() maps every field —
     * absent ones as null — so writing it on update wipes them.
     */
    public function updateData(): array
    {
        return array_filter(
            $this->leadData(),
            fn (string $field) => $this->has($field),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function coreRules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:255'],
            'phone'                => ['required', 'string', 'max:20'],
            'email'                => ['nullable', 'email', 'max:255'],
            'company'              => ['nullable', 'string', 'max:255'],
            'designation'          => ['nullable', 'string', 'max:255'],
            'city'                 => ['nullable', 'string', 'max:100'],
            'state'                => ['nullable', 'string', 'max:100'],
            'source'               => ['nullable', 'in:' . implode(',', array_keys(Lead::sources()))],
            'status'               => ['nullable', 'in:' . implode(',', array_keys(Lead::statuses()))],
            'priority'             => ['nullable', 'in:low,medium,high'],
            'lead_value'           => ['nullable', 'numeric', 'min:0'],
            // Tenant-scoped: an assignee from another tenant is a validation error,
            // not a silent fall-back to "Unassigned".
            'assigned_to'          => ['nullable', Rule::exists('users', 'id')->where('tenant_id', auth()->user()?->tenant_id)],
            'notes'                => ['nullable', 'string', 'max:5000'],
            // 'lost_reason'          => ['nullable', 'string', 'max:500'],
            'expected_close_date'  => ['nullable', 'date', 'after:today'],
        ];
    }
    private function customFieldRules(): array
    {
        $rules  = [];
        $fields = $this->getCustomFields();

        foreach ($fields as $field) {
            $key  = "custom_fields.{$field['id']}";
            $type = $field['field_type'] ?? 'text';

            // ✅ is_required = true  → required
            // ✅ is_required = false → nullable
            $fieldRules = ($field['is_required'] ?? false)
                ? ['required']
                : ['nullable'];

            // Type-specific validation
            $typeRules = match ($type) {
                'email'        => ['email', 'max:255'],
                'url'          => ['url', 'max:500'],
                'number'       => ['numeric'],
                'date'         => ['date'],
                'datetime'     => ['date'],
                'checkbox'     => [],  // 0 or 1 — no strict rule needed
                'dropdown'     => $this->inRule($field['options'] ?? []),
                'multi_select' => ['array'],
                'textarea'     => ['string', 'max:5000'],
                'phone'        => ['string', 'max:20'],
                'text'         => ['string', 'max:500'],
                default        => ['string', 'max:500'],
            };

            $rules[$key] = array_merge($fieldRules, $typeRules);

            // Multi select — each item must be valid option
            if ($type === 'multi_select' && !empty($field['options'])) {
                $rules["{$key}.*"] = [
                    'string',
                    'in:' . implode(',', $field['options'])
                ];
            }
        }

        return $rules;
    }

    private function customFieldMessages(): array
    {
        $messages = [];

        foreach ($this->getCustomFields() as $field) {
            $key   = "custom_fields.{$field['id']}";
            $label = $field['label'] ?? 'Field';

            $messages["{$key}.required"] = "{$label} bharana zaroori hai.";
            $messages["{$key}.email"]    = "{$label} valid email honi chahiye.";
            $messages["{$key}.url"]      = "{$label} valid URL honi chahiye (e.g. https://...).";
            $messages["{$key}.numeric"]  = "{$label} mein sirf number enter karo.";
            $messages["{$key}.date"]     = "{$label} valid date honi chahiye.";
            $messages["{$key}.in"]       = "{$label} mein ek valid option select karo.";
            $messages["{$key}.max"]      = "{$label} bahut lamba hai.";
            $messages["{$key}.*.in"]     = "{$label} mein ek ya zyada invalid options hain.";
        }

        return $messages;
    }

    private function customFieldAttributes(): array
    {
        $attributes = [];

        foreach ($this->getCustomFields() as $field) {
            $attributes["custom_fields.{$field['id']}"] = $field['label'] ?? 'Custom Field';
        }

        return $attributes;
    }

    private function getCustomFields(): \Illuminate\Support\Collection
    {
        return TenantFieldAssignment::getActiveFields(
            auth()->user()->tenant_id,
            'lead'
        );
    }

    private function inRule(array $options): array
    {
        if (empty($options)) return [];
        return ['in:' . implode(',', $options)];
    }

    // Auto-set created_by on store
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('POST')) {
            $this->merge([
                'created_by' => Auth::id(),
                'status'     => $this->status ?? 'new',
                'priority'   => $this->priority ?? 'medium',
                'source'     => $this->source ?? 'other',
            ]);
        }
    }
}
