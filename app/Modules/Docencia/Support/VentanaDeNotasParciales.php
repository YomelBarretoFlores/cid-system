<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Academico\Data\DatosDelCurso;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

// Anexo III 4.1: partial grades are open from the first day of classes to the last day of the acta calendar, and never once the acta exists.
final class VentanaDeNotasParciales
{
    public static function asegurar(DatosDelCurso $curso, ?EstadoActa $acta, CarbonImmutable $momento): void
    {
        $motivo = self::motivoDelCierre($curso, $acta, $momento);

        if ($motivo !== null) {
            throw new ReglaDeNegocioIncumplida($motivo);
        }
    }

    public static function motivoDelCierre(DatosDelCurso $curso, ?EstadoActa $acta, CarbonImmutable $momento): ?string
    {
        // A rectification edits the acta itself, so an acta in rectification also freezes the pre-acta.
        if ($acta instanceof EstadoActa) {
            return "El acta final del grupo {$curso->codigo} ya fue validada; sus notas parciales ya no se modifican.";
        }

        if (! $curso->actasFin instanceof CarbonImmutable) {
            return "El periodo {$curso->periodo} no tiene fechas para actas finales; pide a coordinación que las registre.";
        }

        $dia = $momento->setTimezone('America/Lima')->toDateString();

        if ($dia >= $curso->fechaInicio->toDateString() && $dia <= $curso->actasFin->toDateString()) {
            return null;
        }

        return "Las notas parciales del grupo {$curso->codigo} se registran del {$curso->fechaInicio->format('d/m/Y')} al {$curso->actasFin->format('d/m/Y')}.";
    }
}
