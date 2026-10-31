<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Docencia\Enums\ConfirmacionDeClase;
use App\Modules\Docencia\Enums\EstadoDeClase;
use App\Modules\Docencia\Enums\Llamado;
use App\Modules\Docencia\Models\ClaseDictada;
use Carbon\CarbonImmutable;

final class EstadoDeLaClase
{
    // "Sin confirmar" and "sin llamado de cierre" are not stored: they depend on the time the class is read.
    public static function de(ClaseDictada $clase, ?CarbonImmutable $ahora = null): EstadoDeClase
    {
        if ($clase->registro_posterior) {
            return EstadoDeClase::RegistroPosterior;
        }

        if ($clase->llamado_inicio_en === null) {
            return EstadoDeClase::RegistroAnterior;
        }

        $ahora ??= ClasesDelHorario::ahora();
        $ventanas = VentanasDeLlamado::deLaClase($clase);

        return match ($clase->confirmacion) {
            ConfirmacionDeClase::Confirmada => EstadoDeClase::Confirmada,
            ConfirmacionDeClase::Observada => EstadoDeClase::Observada,
            ConfirmacionDeClase::Pendiente => $ahora->gt($ventanas->venceConfirmacion()) ? EstadoDeClase::SinConfirmar : EstadoDeClase::PorConfirmar,
            null => $ahora->gt($ventanas->cierraCierre()) ? EstadoDeClase::SinLlamadoDeCierre : EstadoDeClase::LlamadoDeInicio,
        };
    }

    // The second call wins in the overlap of both windows once the first call was taken.
    public static function llamadoPara(?ClaseDictada $clase, VentanasDeLlamado $ventanas, ?CarbonImmutable $ahora = null): Llamado
    {
        $ahora ??= ClasesDelHorario::ahora();

        if ($clase?->llamado_inicio_en === null) {
            return $ventanas->inicioAbierto($ahora) ? Llamado::Inicio : Llamado::Posterior;
        }

        if (! in_array($clase->confirmacion, [null, ConfirmacionDeClase::Pendiente], true)) {
            return Llamado::Cerrado;
        }

        if ($ventanas->cierreAbierto($ahora)) {
            return Llamado::Cierre;
        }

        return $ventanas->inicioAbierto($ahora) ? Llamado::Inicio : Llamado::Cerrado;
    }
}
