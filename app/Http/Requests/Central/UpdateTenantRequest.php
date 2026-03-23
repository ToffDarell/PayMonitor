<?php

declare(strict_types=1);

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')],
            'subscription_due_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'overdue', 'suspended', 'inactive'])],
        ];
    }
}
