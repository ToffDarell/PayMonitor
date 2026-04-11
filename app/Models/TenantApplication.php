<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'cooperative_name',
        'cda_registration_number',
        'address',
        'city',
        'contact_number',
        'email',
        'admin_name',
        'admin_email',
        'plan_id',
        'payment_amount',
        'payment_reference',
        'payment_proof_path',
        'payment_status',
        'payment_verified_by',
        'payment_verified_at',
        'message',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_verified_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function paymentVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public function getDomainAttribute(): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '', $this->cooperative_name));
    }

    public function paymentProofIsImage(): bool
    {
        if (blank($this->payment_proof_path)) {
            return false;
        }

        return in_array(strtolower((string) pathinfo($this->payment_proof_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }
}
