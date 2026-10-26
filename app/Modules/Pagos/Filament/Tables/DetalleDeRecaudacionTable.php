<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Tables;

use App\Modules\Pagos\Data\DetalleDeRecaudacion;
use App\Modules\Pagos\Data\ReporteDeRecaudacion;
use App\Modules\Pagos\Enums\MedioPago;
use App\Shared\Support\Filament\IdiomaConBandera;
use App\Shared\Ui\Formato;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

// The detail comes from the report already built for the page, so search, filters, sorting and pages work on that list.
/**
 * @phpstan-type Fila array{estudiante: string, documento: string, concepto: string, idioma: string, nivel: string, periodo: string, medio_pago: string, numero_operacion: string, fecha_pago: CarbonImmutable, monto: int}
 */
final class DetalleDeRecaudacionTable
{
    private const string SIN_IDIOMA = 'Sin idioma';

    /**
     * @param  Closure(): list<DetalleDeRecaudacion>  $detalle
     */
    public static function configure(Table $table, Closure $detalle): Table
    {
        return $table
            ->heading('Detalle por estudiante')
            ->records(fn (?string $search, ?string $sortColumn, ?string $sortDirection, array $filters, int $page, int $recordsPerPage): LengthAwarePaginator => self::pagina(
                self::filas($detalle()),
                $search,
                $filters,
                [$sortColumn, $sortDirection],
                [$page, $recordsPerPage],
            ))
            ->columns([
                TextColumn::make('estudiante')->label('Estudiante')->weight(FontWeight::Medium)->description(fn (array $record): string => $record['documento'])->sortable(),
                TextColumn::make('concepto')->label('Concepto')->description(fn (array $record): ?string => $record['periodo'] === '—' ? null : $record['periodo'])->wrap(),
                TextColumn::make('idioma')->label('Idioma y nivel')->formatStateUsing(fn (array $record): HtmlString|string => $record['idioma'] === self::SIN_IDIOMA
                    ? self::SIN_IDIOMA
                    : IdiomaConBandera::html($record['idioma'], "{$record['idioma']} · {$record['nivel']}")),
                TextColumn::make('medio_pago')->label('Medio de pago')->color('gray'),
                TextColumn::make('numero_operacion')->label('Operación')->color('gray'),
                TextColumn::make('fecha_pago')->label('Fecha')->formatStateUsing(fn (array $record): string => Formato::fechaCorta($record['fecha_pago']))->color('gray')->sortable(),
                TextColumn::make('monto')->label('Monto')->formatStateUsing(fn (array $record): string => 'S/ '.ReporteDeRecaudacion::soles($record['monto']))->weight(FontWeight::SemiBold)->alignEnd()->sortable(),
            ])
            ->filters([
                SelectFilter::make('medio_pago')->label('Medio de pago')->options(collect(MedioPago::cases())->mapWithKeys(fn (MedioPago $medio): array => [$medio->etiqueta() => $medio->etiqueta()])->all()),
                SelectFilter::make('idioma')->label('Idioma')->options(fn (): array => collect($detalle())->pluck('idioma')->unique()->sort()->mapWithKeys(fn (string $idioma): array => [$idioma => $idioma])->all()),
            ])
            ->searchable()
            ->searchPlaceholder('Estudiante, documento u operación')
            ->defaultSort('fecha_pago')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No hay pagos validados en el rango elegido.');
    }

    /**
     * @param  list<DetalleDeRecaudacion>  $detalle
     * @return Collection<string, Fila>
     */
    private static function filas(array $detalle): Collection
    {
        return collect($detalle)->mapWithKeys(fn (DetalleDeRecaudacion $fila, int $indice): array => ["fila-{$indice}" => [
            'estudiante' => $fila->estudiante,
            'documento' => $fila->documento,
            'concepto' => $fila->concepto,
            'idioma' => $fila->idioma,
            'nivel' => $fila->nivel,
            'periodo' => $fila->periodo,
            'medio_pago' => $fila->medioPago,
            'numero_operacion' => $fila->numeroOperacion,
            'fecha_pago' => $fila->fechaPago,
            'monto' => $fila->centimos,
        ]]);
    }

    /**
     * @param  Collection<string, Fila>  $filas
     * @param  array<string, mixed>  $filtros
     * @param  array{?string, ?string}  $orden
     * @param  array{int, int}  $pagina
     * @return LengthAwarePaginator<string, Fila>
     */
    private static function pagina(Collection $filas, ?string $busqueda, array $filtros, array $orden, array $pagina): LengthAwarePaginator
    {
        [$columna, $direccion] = $orden;
        [$numero, $porPagina] = $pagina;

        $filas = $filas
            ->when(filled($busqueda), fn (Collection $todas): Collection => $todas->filter(fn (array $fila): bool => self::coincide($fila, (string) $busqueda)))
            ->when(self::valor($filtros, 'medio_pago'), fn (Collection $todas, string $medio): Collection => $todas->where('medio_pago', $medio))
            ->when(self::valor($filtros, 'idioma'), fn (Collection $todas, string $idioma): Collection => $todas->where('idioma', $idioma))
            ->when($columna, fn (Collection $todas, string $columna): Collection => $todas->sortBy(
                fn (array $fila): mixed => $columna === 'estudiante' ? self::normalizado($fila['estudiante']) : $fila[$columna] ?? null,
                descending: $direccion === 'desc',
            ));

        return new LengthAwarePaginator($filas->forPage($numero, $porPagina), $filas->count(), $porPagina, $numero);
    }

    /**
     * @param  Fila  $fila
     */
    private static function coincide(array $fila, string $busqueda): bool
    {
        $texto = self::normalizado(implode(' ', [$fila['estudiante'], $fila['documento'], $fila['numero_operacion'], $fila['concepto']]));

        return str_contains($texto, self::normalizado($busqueda));
    }

    // Same rule as the panel searches: accents and capitals do not matter.
    private static function normalizado(string $texto): string
    {
        return Str::lower(Str::ascii(trim($texto)));
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private static function valor(array $filtros, string $filtro): ?string
    {
        $valor = $filtros[$filtro]['value'] ?? null;

        return is_string($valor) && $valor !== '' ? $valor : null;
    }
}
