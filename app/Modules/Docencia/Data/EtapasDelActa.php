<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

use App\Modules\Certificacion\Enums\EstadoActa;
use App\Shared\Ui\Formato;
use Carbon\CarbonImmutable;

// The three stages a teacher goes through to close an acta, in the shape of the steps view: [number, label, state, kind].
final readonly class EtapasDelActa
{
    /**
     * @return list<array{int, string, string, 'hecho'|'actual'|'pendiente'}>
     */
    public static function de(int $registradas, int $esperadas, ?EstadoActa $acta, ?CarbonImmutable $inicio, ?CarbonImmutable $fin, CarbonImmutable $hoy): array
    {
        $cerrada = $acta === EstadoActa::Cerrada;
        $completas = $esperadas > 0 && $registradas >= $esperadas;
        $ventanaAbierta = $inicio instanceof CarbonImmutable && $fin instanceof CarbonImmutable && $hoy->startOfDay()->betweenIncluded($inicio, $fin);

        return [
            [1, 'Registra las notas', "{$registradas} de {$esperadas} notas", $cerrada || $completas ? 'hecho' : 'actual'],
            [2, 'Revisa el acta', match (true) {
                $cerrada => 'Revisada',
                $completas => 'Ábrela en Acta final',
                default => 'Cuando completes las notas',
            }, match (true) {
                $cerrada => 'hecho',
                $completas => 'actual',
                default => 'pendiente',
            }],
            [3, 'Valida y cierra', self::ventana($cerrada, $ventanaAbierta, $inicio, $fin), match (true) {
                $cerrada => 'hecho',
                $ventanaAbierta && $completas => 'actual',
                default => 'pendiente',
            }],
        ];
    }

    private static function ventana(bool $cerrada, bool $abierta, ?CarbonImmutable $inicio, ?CarbonImmutable $fin): string
    {
        return match (true) {
            $cerrada => 'Cerrada',
            ! $inicio instanceof CarbonImmutable || ! $fin instanceof CarbonImmutable => 'Sin fechas de actas',
            $abierta => 'Hasta el '.Formato::diaYMes($fin),
            default => 'Del '.Formato::diaYMes($inicio).' al '.Formato::diaYMes($fin),
        };
    }
}
