<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'generated_password' => ['nullable', 'string', 'min:10', 'max:255'],
            'role' => ['required', Rule::in(['tenant_admin', 'branch_manager', 'loan_officer', 'cashier', 'viewer'])],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
        ];
    }
}
