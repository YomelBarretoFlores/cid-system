<?php

declare(strict_types=1);

use App\Modules\Matricula\Actions\ExpirarSolicitudesDeClasificacion;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('clasificacion:expirar', function (ExpirarSolicitudesDeClasificacion $expirar): void {
    $this->info('Solicitudes de clasificación vencidas: '.$expirar());
})->purpose('Mark placement test requests past their date as expired');

Schedule::command('clasificacion:expirar')->dailyAt('00:10')->timezone('America/Lima');

// Expired API tokens stop working at once; this only keeps the table small.
Schedule::command('sanctum:prune-expired --hours=24')->dailyAt('00:20')->timezone('America/Lima');
