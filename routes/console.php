<?php

declare(strict_types=1);

use App\Modules\Matricula\Actions\ExpirarSolicitudesDeClasificacion;
use App\Shared\Auth\Actions\ReiniciarDobleFactor;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('clasificacion:expirar', function (ExpirarSolicitudesDeClasificacion $expirar): void {
    $this->info('Solicitudes de clasificación vencidas: '.$expirar());
})->purpose('Mark placement test requests past their date as expired');

Artisan::command('auth:reiniciar-doble-factor {correo}', function (ReiniciarDobleFactor $reiniciar, string $correo): int {
    try {
        $sesionesCerradas = $reiniciar($correo);
    } catch (ReglaDeNegocioIncumplida $error) {
        $this->error($error->getMessage());

        return 1;
    }

    $this->info('Se quitó el doble factor de la cuenta. Deberá configurarlo de nuevo al ingresar al panel.');

    if (! $sesionesCerradas) {
        $this->warn('Las sesiones abiertas de la cuenta no se cerraron porque no se guardan en la base de datos (SESSION_DRIVER).');
    }

    return 0;
})->purpose('Clear the authenticator app of an account that lost its phone and its recovery codes');

Schedule::command('clasificacion:expirar')->dailyAt('00:10')->timezone('America/Lima');

// Expired API tokens stop working at once; this only keeps the table small.
Schedule::command('sanctum:prune-expired --hours=24')->dailyAt('00:20')->timezone('America/Lima');
