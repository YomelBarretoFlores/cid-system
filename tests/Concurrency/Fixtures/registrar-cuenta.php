<?php

declare(strict_types=1);

use App\Modules\Personas\Actions\RegistrarCuentaDeEstudiante;
use App\Modules\Personas\Data\SolicitudDeCuenta;
use App\Modules\Personas\Enums\TipoDocumento;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';
[, $inicio, $documento, $correo] = $argv;
config(['mail.default' => 'array']);
esperarHasta((float) $inicio);

try {
    DB::transaction(function () use ($app, $documento, $correo): void {
        $app->make(RegistrarCuentaDeEstudiante::class)(new SolicitudDeCuenta(
            tipoDocumento: TipoDocumento::Dni,
            numeroDocumento: $documento,
            apellidoPaterno: 'Quispe',
            apellidoMaterno: null,
            nombres: 'Rosa',
            fechaNacimiento: CarbonImmutable::parse('2001-05-14'),
            correo: $correo,
            celular: null,
            aceptaTratamiento: true,
            password: 'clave-segura1',
        ));
        // Keeps the new rows uncommitted so the other process must reach its insert meanwhile.
        usleep(500_000);
    });
    echo 'registrada';
} catch (ReglaDeNegocioIncumplida $error) {
    echo $error->getMessage();
}
