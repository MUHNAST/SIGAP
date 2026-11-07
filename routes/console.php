<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command('notification:generate')->daily()->withoutOverlapping();
// Schedule::command('app:clean-login-devices')->daily()->withoutOverlapping();
Schedule::command('notification:generate')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('app:clean-login-devices')->hourly()->withoutOverlapping();
Schedule::command('employee:sync-tmt')->daily()->withoutOverlapping();
