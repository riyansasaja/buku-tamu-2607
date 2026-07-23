<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('visits:purge-expired')
    ->yearlyOn(1, 2, '02:30')
    ->timezone((string) config('retention.timezone'))
    ->when(fn (): bool => (bool) config('retention.automatic_enabled'))
    ->withoutOverlapping();
