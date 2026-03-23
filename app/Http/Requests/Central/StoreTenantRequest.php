<?php

declare(strict_types=1);

namespace App\Http\Requests\Central;

use App\Models\Domain;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
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
            'domain' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $fullDomain = strtolower((string) $value).'.'.config('tenancy.tenant_base_domain', 'localhost');

                    if (Domain::query()->where('domain', $fullDomain)->exists()) {
                        $fail('The domain has already been taken.');
                    }
                },
            ],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'plan_id' => ['required', 'integer', Rule::exists('plans', 'id')],
            'subscription_due_at' => ['nullable', 'date'],
        ];
    }
}
