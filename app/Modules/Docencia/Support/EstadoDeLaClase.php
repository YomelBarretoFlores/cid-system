<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Docencia\Enums\ConfirmacionDeClase;
use App\Modules\Docencia\Enums\EstadoDeClase;
use App\Modules\Docencia\Enums\Llamado;
use App\Modules\Docencia\Models\ClaseDictada;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;

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

    /**
     * The same rule as de(), in SQL, so coordination can filter and paginate by state.
     *
     * @param  Builder<ClaseDictada>  $consulta
     * @return Builder<ClaseDictada>
     */
    public static function filtrar(Builder $consulta, EstadoDeClase $estado, ?CarbonImmutable $ahora = null): Builder
    {
        $ahora = ($ahora ?? ClasesDelHorario::ahora())->format('Y-m-d H:i:s.uP');

        return match ($estado) {
            EstadoDeClase::RegistroPosterior => $consulta->where('registro_posterior', true),
            EstadoDeClase::RegistroAnterior => $consulta->where('registro_posterior', false)->whereNull('llamado_inicio_en'),
            EstadoDeClase::Confirmada => $consulta->where('confirmacion', ConfirmacionDeClase::Confirmada),
            EstadoDeClase::Observada => $consulta->where('confirmacion', ConfirmacionDeClase::Observada),
            EstadoDeClase::PorConfirmar => self::vencida($consulta->where('confirmacion', ConfirmacionDeClase::Pendiente), 'confirmacion_minutos_despues_del_fin', $ahora, false),
            EstadoDeClase::SinConfirmar => self::vencida($consulta->where('confirmacion', ConfirmacionDeClase::Pendiente), 'confirmacion_minutos_despues_del_fin', $ahora, true),
            EstadoDeClase::LlamadoDeInicio => self::vencida($consulta->whereNull('confirmacion')->whereNotNull('llamado_inicio_en'), 'cierre_cierra_minutos_despues_del_fin', $ahora, false),
            EstadoDeClase::SinLlamadoDeCierre => self::vencida($consulta->whereNull('confirmacion')->whereNotNull('llamado_inicio_en'), 'cierre_cierra_minutos_despues_del_fin', $ahora, true),
        };
    }

    // The second call wins in the overlap of both windows once the first call was taken.
    public static function llamadoPara(?ClaseDictada $clase, VentanasDeLlamado $ventanas, ?CarbonImmutable $ahora = null): Llamado
    {
        $ahora ??= ClasesDelHorario::ahora();

        if ($clase?->llamado_inicio_en === null) {
            return match (true) {
                $ventanas->inicioAbierto($ahora) => Llamado::Inicio,
                $clase instanceof ClaseDictada && ! $clase->registro_posterior => Llamado::Anterior,
                default => Llamado::Posterior,
            };
        }

        if (! in_array($clase->confirmacion, [null, ConfirmacionDeClase::Pendiente], true)) {
            return Llamado::Cerrado;
        }

        if ($ventanas->cierreAbierto($ahora)) {
            return Llamado::Cierre;
        }

        return $ventanas->inicioAbierto($ahora) ? Llamado::Inicio : Llamado::Cerrado;
    }

    /**
     * Whether the end of the class in Lima (ClasesDelHorario::ZONA) plus the configured minutes is already behind $ahora.
     *
     * @param  Builder<ClaseDictada>  $consulta
     * @return Builder<ClaseDictada>
     */
    private static function vencida(Builder $consulta, string $clave, string $ahora, bool $vencida): Builder
    {
        $valores = [Config::integer("ciln.asistencia.{$clave}"), $ahora];

        return $vencida
            ? $consulta->whereRaw("((fecha + hora_fin) AT TIME ZONE 'America/Lima') + make_interval(mins => ?) < ?::timestamptz", $valores)
            : $consulta->whereRaw("((fecha + hora_fin) AT TIME ZONE 'America/Lima') + make_interval(mins => ?) >= ?::timestamptz", $valores);
    }
}
