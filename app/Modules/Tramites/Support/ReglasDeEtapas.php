<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// What makes a trámite's stages a valid pipeline; the panel checks it on save and a request checks it again.
final class ReglasDeEtapas
{
    /**
     * @param  list<array{nombre: string, avance: int|string, condicion: CondicionDeEtapa}>  $etapas
     */
    public static function validar(array $etapas, bool $cobraTasa): void
    {
        $problema = self::problema($etapas, $cobraTasa);

        throw_if($problema !== null, ReglaDeNegocioIncumplida::class, (string) $problema);
    }

    /**
     * @param  list<array{nombre: string, avance: int|string, condicion: CondicionDeEtapa}>  $etapas
     */
    private static function problema(array $etapas, bool $cobraTasa): ?string
    {
        $avances = array_map(intval(...), array_column($etapas, 'avance'));
        $condiciones = array_column($etapas, 'condicion');
        $pagos = array_keys($condiciones, CondicionDeEtapa::PagoValidado, true);

        return match (true) {
            count($etapas) < 2 => 'El trámite necesita al menos dos etapas.',
            $avances[0] !== 0 || $condiciones[0] !== CondicionDeEtapa::Solicitud => 'La primera etapa es la solicitud recibida, con 0 % de avance.',
            array_keys($condiciones, CondicionDeEtapa::Solicitud, true) !== [0] => 'Solo la primera etapa se alcanza al recibir la solicitud.',
            end($avances) !== 100 => 'La última etapa debe tener 100 % de avance.',
            end($condiciones) !== CondicionDeEtapa::AccionDelPersonal => 'La última etapa la avanza el personal, que es quien cierra el trámite.',
            ! self::crecen($avances) => 'El avance debe crecer de una etapa a la siguiente.',
            $cobraTasa && $pagos !== [1] => 'Un trámite con tasa tiene una sola etapa de pago validado, justo después de la solicitud.',
            ! $cobraTasa && $pagos !== [] => 'Un trámite sin tasa no tiene etapa de pago validado.',
            default => null,
        };
    }

    /**
     * @param  list<int>  $avances
     */
    private static function crecen(array $avances): bool
    {
        return array_all(array_slice($avances, 1), fn (int $avance, $indice): bool => $avance > $avances[$indice]);
    }
}
