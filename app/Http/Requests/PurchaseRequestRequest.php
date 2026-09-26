<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            // Minted when the create form renders; makes a re-submit idempotent.
            'submission_token'     => ['nullable', 'string', 'max:64'],
            'department_id'        => ['nullable', 'integer', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
            'date'                 => ['required', 'date'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['nullable', 'integer', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'items.*.name'         => ['required', 'string', 'max:255'],
            'items.*.description'  => ['nullable', 'string', 'max:500'],
            'items.*.quantity'     => ['required', 'numeric', 'min:0.01'],
            'items.*.reason'       => ['nullable', 'string', 'max:500'],
            'reason'               => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'              => 'Date is required.',
            'items.required'             => 'At least one item is required.',
            'items.min'                  => 'At least one item is required.',
            'items.*.name.required'      => 'Item name is required.',
            'items.*.quantity.required'  => 'Quantity is required.',
            'items.*.quantity.min'       => 'Quantity must be greater than zero.',
        ];
    }

    public function attributes(): array
    {
        return [
            'items.*.name'     => 'item name',
            'items.*.quantity' => 'quantity',
        ];
    }
}
