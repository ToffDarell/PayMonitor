<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Crypt;

class Encrypted implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): mixed
    {
        return filled($value) ? Crypt::decryptString((string) $value) : null;
    }

    public function set($model, string $key, $value, array $attributes): mixed
    {
        return filled($value) ? Crypt::encryptString((string) $value) : null;
    }
}
