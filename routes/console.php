<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Comando rápido para enviar reporte inmediato
Artisan::command('reporte:ahora', function () {
    $this->info('Enviando reporte del momento actual...');
    Artisan::call('report:daily-activity', ['--now' => true]);
    $this->info('Reporte enviado exitosamente a monsterpark1000@gmail.com');
})->purpose('Enviar reporte de actividades del momento actual');

// Programar la limpieza de actividad de usuarios cada minuto para detectar desconexiones rápidamente
Schedule::command('users:clean-activity')->everyMinute();

// Programar el envío del reporte diario de actividades automáticamente a las 12:00 AM (medianoche)
// hora de Ciudad de México para reportar las actividades del día anterior
Schedule::command('report:daily-activity')
    ->dailyAt('00:00')
    ->timezone('America/Mexico_City')
    ->name('reporte-diario-actividades')
    ->withoutOverlapping()
    ->runInBackground();
