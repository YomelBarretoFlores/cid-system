<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Shared\Support\Notas\Nota;

// A student's final grades come only from closed actas; an acta under rectification counts again once it is closed.
final class HistorialDeNotas
{
    private const int CICLOS_POR_NIVEL = 4;

    /**
     * @return list<NotaDelHistorial> ordered by cycle and then by validation
     */
    public static function de(int $personaId, string $idioma): array
    {
        return array_values(NotaFinal::query()
            ->join('cer_actas', 'cer_actas.id', '=', 'cer_notas_finales.acta_id')
            ->where('cer_notas_finales.persona_id', $personaId)
            ->where('cer_actas.idioma', $idioma)
            ->where('cer_actas.estado', EstadoActa::Cerrada)
            ->orderBy('cer_actas.ciclo_numero')
            ->orderBy('cer_actas.validada_en')
            ->get(['cer_notas_finales.acta_id', 'cer_notas_finales.promedio', 'cer_notas_finales.estado_final', 'cer_actas.idioma', 'cer_actas.nivel', 'cer_actas.ciclo_numero', 'cer_actas.ciclo_nombre', 'cer_actas.horas_lectivas', 'cer_actas.periodo'])
            ->map(fn (NotaFinal $nota): NotaDelHistorial => new NotaDelHistorial(
                actaId: $nota->acta_id,
                idioma: (string) $nota->getAttribute('idioma'),
                nivel: NivelCurricular::from((string) $nota->getAttribute('nivel')),
                cicloNumero: (int) $nota->getAttribute('ciclo_numero'),
                cicloNombre: (string) $nota->getAttribute('ciclo_nombre'),
                horasLectivas: (int) $nota->getAttribute('horas_lectivas'),
                periodo: (string) $nota->getAttribute('periodo'),
                promedio: $nota->promedio,
                estadoFinal: $nota->estado_final,
            ))
            ->all());
    }

    /**
     * The last passed result of each cycle of the level, or null when one of its cycles is not passed yet.
     *
     * @param  list<NotaDelHistorial>  $historial
     * @return list<NotaDelHistorial>|null
     */
    public static function nivelAprobado(array $historial, NivelCurricular $nivel): ?array
    {
        $aprobadas = [];

        foreach ($historial as $nota) {
            if ($nota->estadoFinal === EstadoFinal::Aprobado) {
                $aprobadas[$nota->cicloNumero] = $nota;
            }
        }

        $ciclos = range(($nivel->orden() - 1) * self::CICLOS_POR_NIVEL + 1, $nivel->orden() * self::CICLOS_POR_NIVEL);
        $delNivel = array_values(array_filter(array_map(fn (int $ciclo): ?NotaDelHistorial => $aprobadas[$ciclo] ?? null, $ciclos)));

        return count($delNivel) === self::CICLOS_POR_NIVEL ? $delNivel : null;
    }

    /**
     * @param  list<NotaDelHistorial>  $notas  passed cycles, so every average is set
     */
    public static function notaFinal(array $notas): string
    {
        $suma = array_sum(array_map(fn (NotaDelHistorial $nota): int => Nota::centesimos((string) $nota->promedio), $notas));

        return Nota::deCentesimos(intdiv($suma * 2 + count($notas), count($notas) * 2));
    }
}
