<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class BillingInvoice extends Model
{
    use CentralConnection;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'amount',
        'due_date',
        'paid_at',
        'status',
        'paymongo_link_id',
        'paymongo_payment_id',
        'payment_url',
        'payment_method',
        'paid_via',
        'notes',
    ];

    protected $with = [
        'tenant.plan',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', 'unpaid');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'overdue');
    }

    public function scopePendingVerification(Builder $query): Builder
    {
        return $query->where('status', 'pending_verification');
    }

    public static function generateInvoiceNumber(?CarbonInterface $date = null): string
    {
        $date ??= now();
        $prefix = 'INV-'.$date->format('Ym').'-';
        $sequence = static::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->count() + 1;

        do {
            $invoiceNumber = sprintf('%s%04d', $prefix, $sequence);
            $sequence++;
        } while (static::query()->where('invoice_number', $invoiceNumber)->exists());

        return $invoiceNumber;
    }

    public static function firstOrCreateForTenantCycle(Tenant $tenant, CarbonInterface $dueDate, ?string $notes = null): self
    {
        $invoice = static::query()
            ->where('tenant_id', $tenant->id)
            ->whereDate('due_date', $dueDate->toDateString())
            ->latest('id')
            ->first();

        if ($invoice instanceof self) {
            return $invoice;
        }

        return static::query()->create([
            'tenant_id' => $tenant->id,
            'invoice_number' => static::generateInvoiceNumber($dueDate),
            'amount' => (float) ($tenant->plan?->price ?? 0),
            'due_date' => $dueDate->toDateString(),
            'status' => $dueDate->lt(today()) ? 'overdue' : 'unpaid',
            'notes' => $notes,
        ]);
    }

    public static function syncOpenInvoiceForTenant(Tenant $tenant, ?string $notes = null): ?self
    {
        if (! $tenant->relationLoaded('plan')) {
            $tenant->loadMissing('plan');
        }

        if ($tenant->subscription_due_at === null || $tenant->plan === null) {
            return null;
        }

        $dueDate = $tenant->subscription_due_at->copy();
        $status = $dueDate->lt(today()) ? 'overdue' : 'unpaid';

        $invoice = static::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', ['unpaid', 'overdue'])
            ->latest('id')
            ->first();

        if ($invoice === null) {
            return static::query()->create([
                'tenant_id' => $tenant->id,
                'invoice_number' => static::generateInvoiceNumber($dueDate),
                'amount' => (float) ($tenant->plan?->price ?? 0),
                'due_date' => $dueDate->toDateString(),
                'status' => $status,
                'notes' => $notes,
            ]);
        }

        $invoice->forceFill([
            'amount' => (float) ($tenant->plan?->price ?? 0),
            'due_date' => $dueDate->toDateString(),
            'status' => $status,
            'notes' => filled($invoice->notes) ? $invoice->notes : $notes,
        ])->save();

        return $invoice;
    }

    public function markPaidAndRenewTenant(): void
    {
        if ($this->status === 'paid') {
            return;
        }

        $connection = config('tenancy.database.central_connection', config('database.default'));

        DB::connection($connection)->transaction(function (): void {
            $tenant = Tenant::query()
                ->lockForUpdate()
                ->with('plan')
                ->findOrFail($this->tenant_id);

            $baseDate = $tenant->subscription_due_at !== null && $tenant->subscription_due_at->greaterThan(today())
                ? $tenant->subscription_due_at
                : today();

            $tenant->forceFill([
                'subscription_due_at' => $baseDate->copy()->addDays(30),
                'status' => 'active',
            ])->save();

            $this->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
            ])->save();

            $this->setRelation('tenant', $tenant);
        });
    }
}
