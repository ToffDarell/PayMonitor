<?php

use App\Console\Commands\MarkOverdueLoans;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:send-reminders')->dailyAt('08:00');
Schedule::command(MarkOverdueLoans::class)->dailyAt('00:01');
Schedule::command('releases:sync')->everyTenMinutes()->withoutOverlapping();
