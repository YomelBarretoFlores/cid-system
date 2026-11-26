<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Support;

use App\Modules\Academico\Data\VentanaDeMatricula;
use App\Modules\Pagos\Contracts\RecargoDeMatricula;
use App\Shared\Ui\Formato;
use Carbon\CarbonImmutable;

// Formal wording of an enrollment window: the surcharge-free end and, when there is one, the late end with its surcharge.
final class PlazoDeMatricula
{
    public static function texto(VentanaDeMatricula $ventana): string
    {
        $regular = 'Matrícula regular hasta el '.Formato::diaDeMes($ventana->fin);

        return $ventana->extemporaneaFin instanceof CarbonImmutable
            ? "{$regular}; extemporánea".self::recargo($ventana->extemporaneaFin).', hasta el '.Formato::diaDeMes($ventana->extemporaneaFin).'.'
            : "{$regular}.";
    }

    public static function recargo(CarbonImmutable $fecha): string
    {
        $porcentaje = app(RecargoDeMatricula::class)($fecha);

        return $porcentaje === null ? '' : ", con recargo de {$porcentaje} %";
    }
}
