<?php

declare(strict_types=1);

use App\Modules\Certificacion\Contracts\MatriculasAprobadas;
use App\Modules\Matricula\Actions\ExpirarSolicitudesDeClasificacion;
use App\Modules\Matricula\Actions\HabilitarCiclosSiguientes;
use App\Shared\Auth\Actions\CerrarSesionesDeCuenta;
use App\Shared\Auth\Actions\CrearAdministradorDelPersonal;
use App\Shared\Auth\Actions\ReiniciarDobleFactor;
use App\Shared\Auth\Enums\CierreDeSesiones;
use App\Shared\Auth\Support\EnlaceDeContrasena;
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

Artisan::command('matricula:recalcular-habilitaciones', function (MatriculasAprobadas $aprobadas, HabilitarCiclosSiguientes $habilitar): void {
    $this->info('Habilitaciones actualizadas: '.$habilitar($aprobadas()));
})->purpose('Enable the next cycle for every student who passed a closed acta');

Artisan::command('auth:reiniciar-doble-factor {correo} {--solo-sesiones : Only cancel "remember me" and close the open sessions, keeping the second factor}', function (ReiniciarDobleFactor $reiniciar, CerrarSesionesDeCuenta $cerrarSesiones, string $correo): int {
    try {
        $cierre = $this->option('solo-sesiones') ? $cerrarSesiones($correo) : $reiniciar($correo);
    } catch (ReglaDeNegocioIncumplida $error) {
        $this->error($error->getMessage());

        return 1;
    }

    if (! $this->option('solo-sesiones')) {
        $this->info('Se quitó el doble factor de la cuenta. Deberá configurarlo de nuevo al ingresar al panel.');
    }

    // Exit code 2: the reset holds, but the account's open sessions are still alive.
    if ($cierre !== CierreDeSesiones::Cerradas) {
        $this->warn($cierre->mensaje());

        return 2;
    }

    $this->info($cierre->mensaje());

    return 0;
})->purpose('Clear the authenticator app of an account that lost its phone and its recovery codes, and close its sessions');

Artisan::command('personal:crear-administrador {correo} {nombre}', function (CrearAdministradorDelPersonal $crear, string $correo, string $nombre): int {
    try {
        $usuario = $crear($correo, $nombre);
    } catch (ReglaDeNegocioIncumplida $error) {
        $this->error($error->getMessage());

        return 1;
    }

    $this->info('La cuenta tiene acceso al panel y todos los permisos.');

    // The account is already granted, so a link that cannot go out now is a warning, not a failed command.
    try {
        EnlaceDeContrasena::enviar($usuario->email);
        $this->info('Se envió a su correo el enlace para crear su contraseña.');
    } catch (ReglaDeNegocioIncumplida $error) {
        $this->warn("Pero no se pudo enviar el enlace para crear su contraseña: {$error->getMessage()} Vuelve a enviarlo desde Usuarios del personal.");
    }

    return 0;
})->purpose('Create or upgrade a staff account with every permission, including managing users, and email it the link to set its password');

Schedule::command('clasificacion:expirar')->dailyAt('00:10')->timezone('America/Lima');

// Expired API tokens stop working at once; this only keeps the table small.
Schedule::command('sanctum:prune-expired --hours=24')->dailyAt('00:20')->timezone('America/Lima');
