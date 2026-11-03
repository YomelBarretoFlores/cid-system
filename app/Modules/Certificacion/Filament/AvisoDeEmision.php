<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament;

use App\Modules\Certificacion\Support\ResumenDeEmision;
use Filament\Notifications\Notification;

final class AvisoDeEmision
{
    /**
     * @param  array{0: string, 1: string}  $documento  singular and plural, like ['constancia emitida', 'constancias emitidas']
     */
    public static function enviar(ResumenDeEmision $resumen, array $documento, string $yaTenian): void
    {
        $emitidos = count($resumen->emitidos);
        $detalle = array_filter([
            $resumen->omitidos > 0 ? "{$yaTenian}: {$resumen->omitidos}." : null,
            $resumen->fallidos === [] ? null : 'No se emitieron: '.implode('; ', $resumen->fallidos),
        ]);

        $aviso = Notification::make()
            ->status($resumen->fallidos === [] ? 'info' : 'warning')
            ->title($emitidos.' '.($emitidos === 1 ? $documento[0] : $documento[1]))
            ->body($detalle === [] ? null : implode(' ', $detalle));

        // A refused student must be read before the notice goes away.
        if ($resumen->fallidos !== []) {
            $aviso->persistent();
        }

        $aviso->send();
    }
}
