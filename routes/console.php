<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('crm:send-birthday-greetings')->dailyAt('09:00');
// Mocks for others
Schedule::command('app:send-appointment-reminders')->everyMinute();
Schedule::command('app:send-post-treatment-follow-ups')->dailyAt('10:00');
