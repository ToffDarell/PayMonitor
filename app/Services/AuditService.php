<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * @var array<int, string>
     */
    private const EXTRA_AUDIT_KEYS = [
        'recipient_email',
        'sent_at',
        'write_off_reason',
        'penalty_event',
    ];

    public function log(string $action, Model $model, array $old, array $new): void
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant) {
            return;
        }

        if (! $tenant->supportsAuditLogs()) {
            return;
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model' => class_basename($model),
            'model_id' => (string) $model->getKey(),
            'old_values' => $this->sanitizePayload($model, $old),
            'new_values' => $this->sanitizePayload($model, $new),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(Model $model, array $payload): array
    {
        $allowedKeys = array_flip(array_merge(array_keys($model->getAttributes()), self::EXTRA_AUDIT_KEYS));
        $sanitized = [];

        foreach ($payload as $key => $value) {
            if (! is_string($key) || ! array_key_exists($key, $allowedKeys)) {
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
