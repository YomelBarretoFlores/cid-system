<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Academico\Contracts\IniciosDePeriodos;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Models\Aprobacion;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Shared\Support\Notas\Nota;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

// A student's final grades come from closed actas (an acta under rectification counts again once it is closed) and from cycles passed without an acta.
final class HistorialDeNotas
{
    private const array COLUMNAS = [
        'cer_notas_finales.acta_id', 'cer_notas_finales.matricula_id', 'cer_notas_finales.promedio', 'cer_notas_finales.estado_final', 'cer_actas.grupo_codigo',
        'cer_actas.idioma', 'cer_actas.nivel', 'cer_actas.ciclo_numero', 'cer_actas.ciclo_nombre', 'cer_actas.horas_lectivas', 'cer_actas.periodo',
        'cer_actas.ciclo_numero_hasta', 'cer_actas.ciclo_nombre_hasta', 'cer_actas.horas_lectivas_hasta', 'cer_actas.validada_en', 'cer_actas.periodo_id',
    ];

    /**
     * An accelerated acta gives one row per cycle it covers, both with its single grade. Within a cycle, rows follow when they were
     * recorded (acta validation or approval registration), so the last passed attempt is the latest one.
     *
     * @return list<NotaDelHistorial> ordered by cycle and then by when each result was recorded
     */
    public static function de(int $personaId, string $idioma): array
    {
        $actas = self::cerradas()
            ->where('cer_notas_finales.persona_id', $personaId)
            ->where('cer_actas.idioma', $idioma)
            ->get(self::COLUMNAS);
        $inicios = self::inicios($actas);
        $sinActa = Aprobacion::query()->where(['persona_id' => $personaId, 'idioma' => $idioma])->get();

        return array_values($actas
            ->flatMap(fn (NotaFinal $nota): array => array_map(fn (NotaDelHistorial $fila): array => [self::momento((string) $nota->getAttribute('validada_en')), $fila], self::notas($nota, $inicios)))
            ->concat($sinActa->map(fn (Aprobacion $aprobacion): array => [self::momento($aprobacion->created_at), self::sinActa($aprobacion)]))
            ->sort(fn (array $a, array $b): int => [$a[1]->cicloNumero, $a[0]] <=> [$b[1]->cicloNumero, $b[0]])
            ->map(fn (array $entrada): NotaDelHistorial => $entrada[1])
            ->all());
    }

    /**
     * @param  list<int>  $matriculaIds
     * @return array<int, NotaDelHistorial> by enrollment id, only enrollments whose acta is closed
     */
    public static function deMatriculas(array $matriculaIds): array
    {
        $actas = self::cerradas()->whereIntegerInRaw('cer_notas_finales.matricula_id', $matriculaIds)->get(self::COLUMNAS);
        $inicios = self::inicios($actas);

        return $actas->mapWithKeys(fn (NotaFinal $nota): array => [$nota->matricula_id => self::notas($nota, $inicios)[0]])->all();
    }

    /**
     * @return array<string, string> languages with closed actas or cycles passed without an acta for the student, by name
     */
    public static function idiomasDe(int $personaId): array
    {
        $idiomas = self::cerradas()
            ->where('cer_notas_finales.persona_id', $personaId)
            ->distinct()
            ->pluck('cer_actas.idioma')
            ->merge(Aprobacion::query()->where('persona_id', $personaId)->distinct()->pluck('idioma'))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return array_combine($idiomas, $idiomas);
    }

    /**
     * The last passed result of each cycle of the level, or null when one of its cycles is not passed yet, the level has none configured
     * or none of its cycles has a grade (a level skipped entirely by placement has no final grade to certify).
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

        $completo = $ciclosDelNivel !== [] && count($delNivel) === count($ciclosDelNivel);

        return $completo && self::notaFinal($delNivel) !== null ? $delNivel : null;
    }

    /**
     * @param  list<NotaDelHistorial>  $notas  passed cycles; those passed without a grade (placement) are left out of the average
     */
    public static function notaFinal(array $notas): ?string
    {
        $centesimos = array_values(array_map(fn (NotaDelHistorial $nota): int => Nota::centesimos((string) $nota->promedio), array_filter($notas, fn (NotaDelHistorial $nota): bool => $nota->promedio !== null)));

        return $centesimos === [] ? null : Nota::deCentesimos(intdiv(array_sum($centesimos) * 2 + count($centesimos), count($centesimos) * 2));
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
     * @param  Collection<int, NotaFinal>  $actas
     * @return array<int, CarbonImmutable> start of each acta's period, loaded at once
     */
    private static function inicios(Collection $actas): array
    {
        return app(IniciosDePeriodos::class)(array_values(array_unique($actas->map(fn (NotaFinal $nota): int => (int) $nota->getAttribute('periodo_id'))->all())));
    }

    private static function momento(CarbonImmutable|string $instante): string
    {
        return CarbonImmutable::parse($instante)->utc()->format('Y-m-d H:i:s.u');
    }

    /**
     * @param  array<int, CarbonImmutable>  $inicios
     * @return non-empty-list<NotaDelHistorial>
     */
    private static function notas(NotaFinal $nota, array $inicios): array
    {
        $horasHasta = $nota->getAttribute('horas_lectivas_hasta');
        $inicio = $inicios[(int) $nota->getAttribute('periodo_id')] ?? null;
        $primera = self::nota($nota, (int) $nota->getAttribute('ciclo_numero'), (string) $nota->getAttribute('ciclo_nombre'), (int) $nota->getAttribute('horas_lectivas') - (int) $horasHasta, $inicio);

        return $horasHasta === null
            ? [$primera]
            : [$primera, self::nota($nota, (int) $nota->getAttribute('ciclo_numero_hasta'), (string) $nota->getAttribute('ciclo_nombre_hasta'), (int) $horasHasta, $inicio)];
    }

    private static function sinActa(Aprobacion $aprobacion): NotaDelHistorial
    {
        return new NotaDelHistorial(
            actaId: null,
            idioma: $aprobacion->idioma,
            nivel: $aprobacion->nivel,
            cicloNumero: $aprobacion->ciclo_numero,
            cicloNombre: $aprobacion->ciclo_nombre,
            horasLectivas: $aprobacion->horas_lectivas ?? 0,
            periodo: null,
            promedio: $aprobacion->nota,
            estadoFinal: EstadoFinal::Aprobado,
            matriculaId: null,
            grupoCodigo: null,
            forma: $aprobacion->forma,
            anio: $aprobacion->fecha->year,
            mes: $aprobacion->fecha->month,
        );
    }

    private static function nota(NotaFinal $nota, int $cicloNumero, string $cicloNombre, int $horasLectivas, ?CarbonImmutable $inicio): NotaDelHistorial
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
            anio: $inicio?->year,
            mes: $inicio?->month,
        );
    }
}
