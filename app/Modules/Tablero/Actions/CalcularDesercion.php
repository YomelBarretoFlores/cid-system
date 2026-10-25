<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Tablero\Data\Desercion;
use App\Modules\Tablero\Data\FilaDeDesercion;
use App\Modules\Tablero\Data\FiltrosDelTablero;
use App\Modules\Tablero\Support\GruposDelPeriodo;
use App\Modules\Tablero\Support\PeriodoConsultado;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use stdClass;

// Desertion is a formal withdrawal; an NSP grade is a grade outcome, not a desertion.
final readonly class CalcularDesercion
{
    public function __invoke(FiltrosDelTablero $filtros): Desercion
    {
        PeriodoConsultado::de($filtros->periodoId);

        $filas = GruposDelPeriodo::consulta($filtros)
            ->join('mat_matriculas as m', 'm.grupo_id', '=', 'g.id')
            ->groupBy('i.nombre', 'c.nivel')
            ->selectRaw('i.nombre as idioma, c.nivel, count(*) as matriculas, count(*) filter (where m.estado = ?) as retiradas', [EstadoMatricula::Retirada->value])
            ->get();

        return new Desercion(
            total: $this->fila('Total', $filas),
            porNivel: $this->agrupar($filas, fn (stdClass $fila): string => (string) $fila->nivel, fn (string $nivel): array => [NivelCurricular::from($nivel)->orden(), NivelCurricular::from($nivel)->etiqueta()]),
            porIdioma: $this->agrupar($filas, fn (stdClass $fila): string => (string) $fila->idioma, fn (string $idioma): array => [Str::lower(Str::ascii($idioma)), $idioma]),
        );
    }

    /**
     * @param  Collection<int, stdClass>  $filas
     * @param  callable(stdClass): string  $clave
     * @param  callable(string): array{0: int|string, 1: string}  $ordenYEtiqueta
     * @return list<FilaDeDesercion>
     */
    private function agrupar(Collection $filas, callable $clave, callable $ordenYEtiqueta): array
    {
        return array_values($filas->groupBy($clave)
            ->map(fn (Collection $grupo, int|string $valor): array => [...$ordenYEtiqueta((string) $valor), $grupo])
            ->sortBy(fn (array $grupo): int|string => $grupo[0])
            ->map(fn (array $grupo): FilaDeDesercion => $this->fila($grupo[1], $grupo[2]))
            ->all());
    }

    /**
     * @param  Collection<int, stdClass>  $filas
     */
    private function fila(string $etiqueta, Collection $filas): FilaDeDesercion
    {
        return new FilaDeDesercion($etiqueta, (int) $filas->sum('matriculas'), (int) $filas->sum('retiradas'));
    }
}
