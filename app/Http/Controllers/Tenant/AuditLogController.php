<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanDocument;
use App\Models\LoanPayment;
use App\Models\LoanType;
use App\Models\Member;
use App\Models\MemberDocument;
use App\Models\User;
use App\Support\TenantPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * @return array<string, string>
     */
    private const MODEL_OPTIONS = [
        'Loan' => 'Loan',
        'Member' => 'Member',
        'LoanPayment' => 'Payment',
        'LoanType' => 'Loan Type',
        'Branch' => 'Branch',
        'User' => 'User',
        'MemberDocument' => 'Member Document',
        'LoanDocument' => 'Loan Document',
    ];

    public function index(Request $request): View
    {
        abort_unless(
            $request->user()?->hasTenantPermission(TenantPermissions::AUDIT_LOGS_VIEW, ['tenant_admin']),
            403,
            'This action is unauthorized.'
        );

        $tenant = tenant();

        abort_unless($tenant?->supportsAuditLogs(), 404);

        $filters = $request->validate([
            'action' => ['nullable', Rule::in(['created', 'updated', 'deleted'])],
            'model' => ['nullable', Rule::in(array_keys(self::MODEL_OPTIONS))],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $users = User::query()
            ->with('roles')
            ->orderBy('name')
            ->get();

        $auditLogs = AuditLog::query()
            ->with(['user.roles'])
            ->when($filters['action'] ?? null, static fn ($query, string $action) => $query->where('action', $action))
            ->when($filters['model'] ?? null, static fn ($query, string $model) => $query->where('model', $model))
            ->when($filters['user_id'] ?? null, static fn ($query, int $userId) => $query->where('user_id', $userId))
            ->when($filters['date_from'] ?? null, static fn ($query, string $dateFrom) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, static fn ($query, string $dateTo) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest('created_at')
            ->paginate(20)
            ->through(function (AuditLog $auditLog): AuditLog {
                $auditLog->setAttribute('module_label', $this->moduleLabel($auditLog->model));
                $auditLog->setAttribute('record_reference', $this->resolveRecordReference($auditLog));
                $auditLog->setAttribute('role_label', $auditLog->user?->getRoleNames()->first()
                    ? TenantPermissions::displayRoleName((string) $auditLog->user?->getRoleNames()->first())
                    : null);
                $auditLog->setAttribute('changes', $this->buildChangeRows(
                    is_array($auditLog->old_values) ? $auditLog->old_values : [],
                    is_array($auditLog->new_values) ? $auditLog->new_values : [],
                ));

                return $auditLog;
            })
            ->withQueryString();

        return view('audit-logs.index', [
            'auditLogs' => $auditLogs,
            'users' => $users,
            'filters' => $filters,
            'actionOptions' => [
                'created' => 'Created',
                'updated' => 'Updated',
                'deleted' => 'Deleted',
            ],
            'modelOptions' => self::MODEL_OPTIONS,
        ]);
    }

    private function moduleLabel(string $model): string
    {
        return self::MODEL_OPTIONS[$model] ?? trim((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $model));
    }

    private function resolveRecordReference(AuditLog $auditLog): string
    {
        $model = $this->resolveModelInstance($auditLog);

        return match ($auditLog->model) {
            'Loan' => '#'.($model?->loan_number ?? $auditLog->model_id),
            'Member' => '#'.($model?->member_number ?? $auditLog->model_id),
            'LoanPayment' => '#PAY-'.str_pad((string) $auditLog->model_id, 4, '0', STR_PAD_LEFT),
            'LoanType' => '#LT-'.str_pad((string) $auditLog->model_id, 4, '0', STR_PAD_LEFT),
            'Branch' => '#BR-'.str_pad((string) $auditLog->model_id, 4, '0', STR_PAD_LEFT),
            'User' => '#USR-'.str_pad((string) $auditLog->model_id, 4, '0', STR_PAD_LEFT),
            'MemberDocument' => '#MDOC-'.str_pad((string) $auditLog->model_id, 4, '0', STR_PAD_LEFT),
            'LoanDocument' => '#LDOC-'.str_pad((string) $auditLog->model_id, 4, '0', STR_PAD_LEFT),
            default => '#'.$auditLog->model_id,
        };
    }

    private function resolveModelInstance(AuditLog $auditLog): ?Model
    {
        return match ($auditLog->model) {
            'Loan' => Loan::query()->find($auditLog->model_id),
            'Member' => Member::query()->find($auditLog->model_id),
            'LoanPayment' => LoanPayment::query()->find($auditLog->model_id),
            'LoanType' => LoanType::query()->find($auditLog->model_id),
            'Branch' => Branch::query()->find($auditLog->model_id),
            'User' => User::query()->find($auditLog->model_id),
            'MemberDocument' => MemberDocument::query()->find($auditLog->model_id),
            'LoanDocument' => LoanDocument::query()->find($auditLog->model_id),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return array<int, array{key:string, old:string, new:string}>
     */
    private function buildChangeRows(array $oldValues, array $newValues): array
    {
        $ignoredKeys = ['password', 'remember_token'];
        $keys = array_values(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))));
        $rows = [];

        foreach ($keys as $key) {
            if (in_array($key, $ignoredKeys, true)) {
                continue;
            }

            $oldValue = $oldValues[$key] ?? null;
            $newValue = $newValues[$key] ?? null;

            if ($this->normalizeValue($oldValue) === $this->normalizeValue($newValue)) {
                continue;
            }

            $rows[] = [
                'key' => trim((string) preg_replace('/[_-]+/', ' ', $key)),
                'old' => $this->formatValue($oldValue),
                'new' => $this->formatValue($newValue),
            ];
        }

        return $rows;
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    private function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            ksort($value);

            return $value;
        }

        return $value;
    }

    /**
     * @param  mixed  $value
     */
    private function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '—';
        }

        return (string) $value;
    }
}
