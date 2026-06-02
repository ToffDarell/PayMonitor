<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'birthdate' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'civil_status' => ['nullable', Rule::in(['single', 'married', 'widowed'])],
            'address' => ['nullable', 'string'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'joined_at' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            // Employment & Financial Information
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            // Co-maker Information
            'co_maker_name' => ['nullable', 'string', 'max:255'],
            'co_maker_address' => ['nullable', 'string', 'max:500'],
            'co_maker_contact_number' => ['nullable', 'string', 'max:20'],
        ];
    }
}
