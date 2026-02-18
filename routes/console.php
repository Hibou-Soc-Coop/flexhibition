<?php

use App\Facades\Settings;
use Cron\CronExpression;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$scheduleEnabled = (bool) Settings::get('backups.schedule_enabled', false);
$scheduleCron = (string) Settings::get('backups.schedule_cron', '');

if ($scheduleEnabled && $scheduleCron !== '' && CronExpression::isValidExpression($scheduleCron)) {
    Schedule::command('backup:create')->cron($scheduleCron);
}
