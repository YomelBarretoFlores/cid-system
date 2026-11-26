<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Shared\Support\Notas\Nota;
use Illuminate\Database\Eloquent\Builder;

// A student's final grades come only from closed actas; an acta under rectification counts again once it is closed.
final class HistorialDeNotas
{
    private const array COLUMNAS = [
        'cer_notas_finales.acta_id', 'cer_notas_finales.matricula_id', 'cer_notas_finales.promedio', 'cer_notas_finales.estado_final', 'cer_actas.grupo_codigo',
        'cer_actas.idioma', 'cer_actas.nivel', 'cer_actas.ciclo_numero', 'cer_actas.ciclo_nombre', 'cer_actas.horas_lectivas', 'cer_actas.periodo',
        'cer_actas.ciclo_numero_hasta', 'cer_actas.ciclo_nombre_hasta', 'cer_actas.horas_lectivas_hasta', 'cer_actas.validada_en',
    ];

    /**
     * An accelerated acta gives one row per cycle it covers, both with its single grade.
     *
     * @return list<NotaDelHistorial> ordered by cycle and then by validation
     */
    public static function de(int $personaId, string $idioma): array
    {
        return array_values(self::cerradas()
            ->where('cer_notas_finales.persona_id', $personaId)
            ->where('cer_actas.idioma', $idioma)
            ->orderBy('cer_actas.validada_en')
            ->get(self::COLUMNAS)
            ->flatMap(self::notas(...))
            ->sortBy(fn (NotaDelHistorial $nota): int => $nota->cicloNumero)
            ->all());
    }

    /**
     * @param  list<int>  $matriculaIds
     * @return array<int, NotaDelHistorial> by enrollment id, only enrollments whose acta is closed
     */
    public static function deMatriculas(array $matriculaIds): array
    {
        return self::cerradas()
            ->whereIntegerInRaw('cer_notas_finales.matricula_id', $matriculaIds)
            ->get(self::COLUMNAS)
            ->mapWithKeys(fn (NotaFinal $nota): array => [$nota->matricula_id => self::notas($nota)[0]])
            ->all();
    }

    /**
     * @return array<string, string> languages with closed actas for the student, by name
     */
    public static function idiomasDe(int $personaId): array
    {
        $idiomas = self::cerradas()
            ->where('cer_notas_finales.persona_id', $personaId)
            ->distinct()
            ->orderBy('cer_actas.idioma')
            ->pluck('cer_actas.idioma')
            ->all();

        return array_combine($idiomas, $idiomas);
    }

    /**
     * The last passed result of each cycle of the level, or null when one of its cycles is not passed yet or the level has none configured.
     *
     * @param  list<NotaDelHistorial>  $historial
     * @param  list<int>  $ciclosDelNivel  the cycles the language configures for the level
     * @return list<NotaDelHistorial>|null
     */
    public static function nivelAprobado(array $historial, array $ciclosDelNivel): ?array
    {
        $aprobadas = [];

        foreach ($historial as $nota) {
            if ($nota->estadoFinal === EstadoFinal::Aprobado) {
                $aprobadas[$nota->cicloNumero] = $nota;
            }
        }

        $delNivel = array_values(array_filter(array_map(fn (int $ciclo): ?NotaDelHistorial => $aprobadas[$ciclo] ?? null, $ciclosDelNivel)));

        return $ciclosDelNivel !== [] && count($delNivel) === count($ciclosDelNivel) ? $delNivel : null;
    }

    /**
     * @param  list<NotaDelHistorial>  $notas  passed cycles, so every average is set
     */
    public static function notaFinal(array $notas): string
    {
        $suma = array_sum(array_map(fn (NotaDelHistorial $nota): int => Nota::centesimos((string) $nota->promedio), $notas));

        return Nota::deCentesimos(intdiv($suma * 2 + count($notas), count($notas) * 2));
    }

    /**
     * @return Builder<NotaFinal>
     */
    private static function cerradas(): Builder
    {
        return NotaFinal::query()
            ->join('cer_actas', 'cer_actas.id', '=', 'cer_notas_finales.acta_id')
            ->where('cer_actas.estado', EstadoActa::Cerrada);
    }

    /**
     * @return non-empty-list<NotaDelHistorial>
     */
    private static function notas(NotaFinal $nota): array
    {
        $horasHasta = $nota->getAttribute('horas_lectivas_hasta');
        $primera = self::nota($nota, (int) $nota->getAttribute('ciclo_numero'), (string) $nota->getAttribute('ciclo_nombre'), (int) $nota->getAttribute('horas_lectivas') - (int) $horasHasta);

        return $horasHasta === null
            ? [$primera]
            : [$primera, self::nota($nota, (int) $nota->getAttribute('ciclo_numero_hasta'), (string) $nota->getAttribute('ciclo_nombre_hasta'), (int) $horasHasta)];
    }

    private static function nota(NotaFinal $nota, int $cicloNumero, string $cicloNombre, int $horasLectivas): NotaDelHistorial
    {
        return new NotaDelHistorial(
            actaId: $nota->acta_id,
            idioma: (string) $nota->getAttribute('idioma'),
            nivel: NivelCurricular::from((string) $nota->getAttribute('nivel')),
            cicloNumero: $cicloNumero,
            cicloNombre: $cicloNombre,
            horasLectivas: $horasLectivas,
            periodo: (string) $nota->getAttribute('periodo'),
            promedio: $nota->promedio,
            estadoFinal: $nota->estado_final,
            matriculaId: $nota->matricula_id,
            grupoCodigo: (string) $nota->getAttribute('grupo_codigo'),
        );
    }
}
