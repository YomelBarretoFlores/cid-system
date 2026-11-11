<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Pages;

use App\Modules\Academico\Contracts\CatalogoDePeriodos;
use App\Modules\Academico\Contracts\PeriodoEnCurso;
use App\Modules\Certificacion\Actions\ListarActasPendientes;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use UnitEnum;

/**
 * @phpstan-import-type ActaPendiente from ListarActasPendientes
 */
final class ActasPendientes extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Certificación';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Actas pendientes';

    protected static ?string $title = 'Actas pendientes';

    protected static ?string $slug = 'actas-pendientes';

    protected ?string $subheading = 'Grupos del periodo que terminaron sus clases, o están abiertos dentro del plazo de actas, y aún no tienen su acta final cerrada.';

    protected string $view = 'certificacion::filament.actas-pendientes';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoCertificacion::Actas->value) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (array $filters, int $page, int $recordsPerPage): LengthAwarePaginator => $this->pagina($filters, $page, $recordsPerPage))
            ->columns([
                TextColumn::make('codigo')->label('Grupo')->weight('medium'),
                TextColumn::make('idioma')->label('Idioma'),
                TextColumn::make('ciclo')->label('Ciclo'),
                TextColumn::make('docente')->label('Docente')->placeholder('Sin docente'),
                TextColumn::make('ventana')->label('Plazo de actas')->placeholder('Sin plazo registrado'),
                TextColumn::make('estado_grupo')->label('Estado del grupo'),
                TextColumn::make('estado_acta')->label('Acta final'),
            ])
            ->filters([
                SelectFilter::make('periodo')->label('Periodo')->options(fn (): array => app(CatalogoDePeriodos::class)())->default(app(PeriodoEnCurso::class)())->selectablePlaceholder(false),
            ])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('Todos los grupos de este periodo tienen su acta final cerrada.');
    }

    /**
     * Without a period chosen the page keeps showing the period in progress, never every period at once.
     *
     * @param  array<string, mixed>  $filtros
     * @return LengthAwarePaginator<int, ActaPendiente>
     */
    private function pagina(array $filtros, int $numero, int $porPagina): LengthAwarePaginator
    {
        $elegido = $filtros['periodo']['value'] ?? null;
        $periodoId = is_numeric($elegido) ? (int) $elegido : app(PeriodoEnCurso::class)();
        $filas = collect($periodoId === null ? [] : app(ListarActasPendientes::class)($periodoId));

        return new LengthAwarePaginator($filas->forPage($numero, $porPagina), $filas->count(), $porPagina, $numero);
    }
}
