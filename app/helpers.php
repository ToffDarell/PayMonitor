<?php

declare(strict_types=1);

use App\Models\TenantSetting;
use Carbon\Carbon;

if (!function_exists('formatDate')) {
    function formatDate(mixed $date, bool $showTime = false): string
    {
        if (!$date) {
            return '—';
        }
        $format = TenantSetting::get('date_format', 'M d, Y');
        if ($showTime) {
            $format .= ' h:i A';
        }
        return Carbon::parse($date)->format($format);
    }
}
