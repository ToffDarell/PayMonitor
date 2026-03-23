<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'loan_type_id' => ['required', 'integer', Rule::exists('loan_types', 'id')],
            'principal_amount' => ['required', 'numeric', 'min:1'],
            'term_months' => ['required', 'integer', 'min:1'],
            'release_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
