<?php

use App\Console\Commands\MarkOverdueLoans;
use App\Console\Commands\SendOverdueReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:send-reminders')->dailyAt('08:00');
Schedule::command(MarkOverdueLoans::class)->dailyAt('00:01');
Schedule::command('releases:sync')->everyTenMinutes()->withoutOverlapping();
Schedule::command(SendOverdueReminders::class)->weeklyOn(1, '08:00')->withoutOverlapping();

// Ensure the server has this cron entry for the scheduler to work:
// * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
