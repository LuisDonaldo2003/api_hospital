<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar la limpieza de actividad de usuarios cada minuto para detectar desconexiones rápidamente
Schedule::command('users:clean-activity')->everyMinute();
