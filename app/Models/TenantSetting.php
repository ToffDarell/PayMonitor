<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TenantSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function defaultSettings(): array
    {
        return [
            'cooperative_tagline' => 'Your trusted lending cooperative',
            'contact_number' => '',
            'contact_email' => '',
            'address' => '',
            'logo_path' => null,
            'accent_color' => 'green',
            'show_member_photos' => '0',
            'currency_symbol' => '₱',
            'date_format' => 'M d, Y',
            'items_per_page' => '15',
        ];
    }

    public static function ensureDefaults(): void
    {
        if (! static::settingsTableExists()) {
            return;
        }

        foreach (static::defaultSettings() as $key => $value) {
            static::query()->firstOrCreate(
                ['key' => $key],
                ['value' => static::normalizeValue($value)],
            );
        }
    }

    public static function allKeyed(): array
    {
        if (! static::settingsTableExists()) {
            return static::defaultSettings();
        }

        static::ensureDefaults();

        return array_replace(
            static::defaultSettings(),
            static::query()->pluck('value', 'key')->all(),
        );
    }

    public static function get(string $key, $default = null)
    {
        if (! static::settingsTableExists()) {
            return $default;
        }

        static::ensureDefaults();

        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        if (! static::settingsTableExists()) {
            return;
        }

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    protected static function normalizeValue(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    protected static function settingsTableExists(): bool
    {
        return Schema::hasTable((new static)->getTable());
    }
}
