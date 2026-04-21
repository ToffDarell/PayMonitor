<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_request_id',
        'responder_name',
        'responder_email',
        'message',
        'sent_via_email',
    ];

    protected function casts(): array
    {
        return [
            'sent_via_email' => 'boolean',
        ];
    }

    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection') ?: parent::getConnectionName();
    }

    public function supportRequest(): BelongsTo
    {
        return $this->belongsTo(SupportRequest::class);
    }
}
