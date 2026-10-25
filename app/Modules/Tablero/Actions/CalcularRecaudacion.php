<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Tablero\Data\FilaDeRecaudacion;
use App\Modules\Tablero\Data\FiltrosDelTablero;
use App\Modules\Tablero\Data\Recaudacion;
use App\Modules\Tablero\Support\PagosConContexto;
use App\Modules\Tablero\Support\PeriodoConsultado;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use stdClass;

final readonly class CalcularRecaudacion
{
    public function __invoke(FiltrosDelTablero $filtros): Recaudacion
    {
        $periodo = PeriodoConsultado::de($filtros->periodoId);

        // An enrollment fee belongs to its group's period; any other payment to the period whose classes include its date.
        $filas = array_values(PagosConContexto::validados()
            ->where(fn (Builder $query): Builder => $query
                ->where('g.periodo_id', $periodo->id)
                ->orWhere(fn (Builder $sinGrupo): Builder => $sinGrupo->whereNull('m.id')->whereBetween('p.fecha_pago', [$periodo->fechaInicio, $periodo->fechaFin])))
            ->when($filtros->idiomaId, fn (Builder $query, int $idiomaId): Builder => $query->where('i.id', $idiomaId))
            ->when($filtros->nivel, fn (Builder $query, NivelCurricular $nivel): Builder => $query->where('c.nivel', $nivel->value))
            ->groupBy('i.nombre', 'c.nivel')
            ->selectRaw('i.nombre as idioma, c.nivel, count(*) as operaciones, sum((p.monto * 100)::bigint) as centimos')
            ->get()
            ->map(fn (stdClass $fila): array => $this->fila($fila))
            ->sortBy(fn (array $fila): array => $fila['orden'])
            ->map(fn (array $fila): FilaDeRecaudacion => $fila['fila'])
            ->all());

        return new Recaudacion(
            totalCentimos: array_sum(array_map(fn (FilaDeRecaudacion $fila): int => $fila->centimos, $filas)),
            operaciones: array_sum(array_map(fn (FilaDeRecaudacion $fila): int => $fila->operaciones, $filas)),
            filas: $filas,
        );
    }

    /**
     * @return array{fila: FilaDeRecaudacion, orden: array{int, string, int}}
     */
    private function fila(stdClass $fila): array
    {
        $idioma = is_string($fila->idioma ?? null) ? $fila->idioma : null;
        $nivel = is_string($fila->nivel ?? null) ? NivelCurricular::from($fila->nivel) : null;

        return [
            'fila' => new FilaDeRecaudacion($idioma ?? 'Sin idioma', $nivel?->etiqueta() ?? 'Sin nivel', (int) $fila->operaciones, (int) $fila->centimos),
            'orden' => [$idioma === null ? 1 : 0, Str::lower(Str::ascii($idioma ?? '')), $nivel?->orden() ?? 9],
        ];
    }
}
