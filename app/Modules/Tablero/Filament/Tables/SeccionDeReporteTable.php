<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Filament\Tables;

use App\Modules\Tablero\Data\ColumnaDeReporte;
use App\Modules\Tablero\Data\SeccionDeReporte;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * One section of a general report on screen: columns c0..cN as the report defines them; search, sorting and pages work on the rows already built.
 *
 * @phpstan-type Fila array<string, string|int|float|null>
 */
final class SeccionDeReporteTable
{
    public static function configure(Table $table, ?SeccionDeReporte $seccion): Table
    {
        $columnas = $seccion instanceof SeccionDeReporte ? $seccion->columnas : [];

        return $table
            ->records(fn (?string $search, ?string $sortColumn, ?string $sortDirection, int $page, int $recordsPerPage): LengthAwarePaginator => self::pagina(
                self::filas($seccion),
                $columnas,
                $search,
                [$sortColumn, $sortDirection],
                [$page, $recordsPerPage],
            ))
            ->columns(array_map(fn (ColumnaDeReporte $columna, int $indice): TextColumn => TextColumn::make("c{$indice}")
                ->label($columna->nombre)
                ->formatStateUsing(fn (string|int|float $state): string => $columna->tipo->mostrar($state))
                ->placeholder('—')
                ->sortable()
                ->alignment($columna->tipo->esNumero() ? 'end' : 'start'), $columnas, array_keys($columnas)))
            ->searchable()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Sin registros para los filtros elegidos.');
    }

    /**
     * @return Collection<string, Fila>
     */
    private static function filas(?SeccionDeReporte $seccion): Collection
    {
        return collect($seccion instanceof SeccionDeReporte ? $seccion->filas : [])->mapWithKeys(fn (array $fila, int $indice): array => [
            "fila-{$indice}" => collect($fila)->mapWithKeys(fn (string|int|float|null $valor, int $columna): array => ["c{$columna}" => $valor])->all(),
        ]);
    }

    /**
     * @param  Collection<string, Fila>  $filas
     * @param  list<ColumnaDeReporte>  $columnas
     * @param  array{?string, ?string}  $orden
     * @param  array{int, int}  $pagina
     * @return LengthAwarePaginator<string, Fila>
     */
    private static function pagina(Collection $filas, array $columnas, ?string $busqueda, array $orden, array $pagina): LengthAwarePaginator
    {
        [$columna, $direccion] = $orden;
        [$numero, $porPagina] = $pagina;
        $textos = array_keys(array_filter($columnas, fn (ColumnaDeReporte $candidata): bool => ! $candidata->tipo->esNumero()));

        $filas = $filas
            ->when(filled($busqueda), fn (Collection $todas): Collection => $todas->filter(fn (array $fila): bool => str_contains(
                self::normalizado(implode(' ', array_map(fn (int $indice): string => (string) ($fila["c{$indice}"] ?? ''), $textos))),
                self::normalizado((string) $busqueda),
            )))
            ->when($columna, fn (Collection $todas, string $columna): Collection => $todas->sortBy(
                fn (array $fila): string|int|float => is_string($fila[$columna] ?? null) ? self::normalizado($fila[$columna]) : $fila[$columna] ?? -1,
                descending: $direccion === 'desc',
            ));

        return new LengthAwarePaginator($filas->forPage($numero, $porPagina), $filas->count(), $porPagina, $numero);
    }

    // Same rule as the panel searches: accents and capitals do not matter.
    private static function normalizado(string $texto): string
    {
        return Str::lower(Str::ascii(trim($texto)));
    }
}
