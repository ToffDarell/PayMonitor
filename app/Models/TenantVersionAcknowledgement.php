<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class TenantVersionAcknowledgement extends Model
{
    use CentralConnection;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'version_id',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(AppVersion::class, 'version_id');
    }
}
