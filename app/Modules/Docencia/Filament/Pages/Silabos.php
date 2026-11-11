<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Pages;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\CatalogoDePeriodos;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Docencia\Contracts\LecturaDelSilabo;
use App\Modules\Docencia\Enums\EstadoSilabo;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Support\SilabosDelPeriodo;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * @phpstan-import-type Fila from SilabosDelPeriodo
 */
final class Silabos extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Docencia';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Sílabos';

    protected static ?string $title = 'Sílabos';

    protected static ?string $slug = 'silabos';

    protected ?string $subheading = 'Sílabo de cada grupo del periodo: los que faltan, los borradores y los publicados para los estudiantes.';

    protected string $view = 'docencia::filament.silabos';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoDocencia::Ver->value) ?? false;
    }

    public function table(Table $table): Table
    {
        $silabos = app(SilabosDelPeriodo::class);

        return $table
            ->records(fn (array $filters, int $page, int $recordsPerPage): LengthAwarePaginator => $this->pagina($silabos, $filters, $page, $recordsPerPage))
            ->columns([
                TextColumn::make('codigo')->label('Grupo')->weight('medium'),
                TextColumn::make('idioma')->label('Idioma y ciclo')->state(fn (array $record): string => "{$record['idioma']} · Ciclo {$record['ciclo']}")->description(fn (array $record): string => $record['nivel']),
                TextColumn::make('docente')->label('Docente')->placeholder('Sin docente'),
                TextColumn::make('estado')
                    ->label('Sílabo')
                    ->badge()
                    ->state(fn (array $record): string => EstadoSilabo::etiquetaDe($record['estado']))
                    ->color(fn (array $record): string => EstadoSilabo::colorDe($record['estado'])),
                TextColumn::make('publicado_en')
                    ->label('Publicado el')
                    ->placeholder('—')
                    ->state(fn (array $record): ?string => $record['publicado_en']?->timezone('America/Lima')->format('d/m/Y H:i')),
            ])
            ->filters([
                SelectFilter::make('periodo')->label('Periodo')->options(fn (): array => app(CatalogoDePeriodos::class)())->default($silabos->periodoPorDefecto())->selectablePlaceholder(false),
                SelectFilter::make('idioma')->label('Idioma')->options(fn (): array => array_column(array_map(fn (IdiomaDelCatalogo $idioma): array => [$idioma->id, $idioma->nombre], app(CatalogoDeIdiomas::class)()), 1, 0)),
                SelectFilter::make('docente')->label('Docente')->searchable()->options(fn (): array => $silabos->docentes()),
                Filter::make('sin_publicar')->label('Sin publicar (falta o borrador)')->toggle(),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->visible(fn (array $record): bool => $record['estado'] !== null)
                    ->modalHeading(fn (array $record): string => "Sílabo del grupo {$record['codigo']}")
                    ->modalWidth('7xl')
                    ->modalContent(fn (array $record): View => view('docencia::filament.silabo', ['silabo' => app(LecturaDelSilabo::class)($record['grupo_id']) ?? abort(404)]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->color('gray')
                    ->visible(fn (array $record): bool => $record['estado'] !== null)
                    ->url(fn (array $record): string => route('panel.silabos.pdf', $record['grupo_id']))
                    ->openUrlInNewTab(),
            ])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No hay grupos con estos filtros.');
    }

    /**
     * Without a period chosen the page keeps showing the default one, never every period at once.
     *
     * @param  array<string, mixed>  $filtros
     * @return LengthAwarePaginator<int, Fila>
     */
    private function pagina(SilabosDelPeriodo $silabos, array $filtros, int $numero, int $porPagina): LengthAwarePaginator
    {
        $periodoId = $this->valor($filtros, 'periodo') ?? $silabos->periodoPorDefecto();
        $filas = $periodoId === null ? collect() : $silabos->filas($periodoId);

        $filas = $filas
            ->when($this->valor($filtros, 'idioma'), fn (Collection $todas, int $idioma): Collection => $todas->where('idioma_id', $idioma))
            ->when($this->valor($filtros, 'docente'), fn (Collection $todas, int $docente): Collection => $todas->where('docente_id', $docente))
            ->when((bool) ($filtros['sin_publicar']['isActive'] ?? false), fn (Collection $todas): Collection => $todas->where('estado', '!==', EstadoSilabo::Publicado));

        return new LengthAwarePaginator($filas->forPage($numero, $porPagina), $filas->count(), $porPagina, $numero);
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function valor(array $filtros, string $filtro): ?int
    {
        $valor = $filtros[$filtro]['value'] ?? null;

        return is_numeric($valor) ? (int) $valor : null;
    }
}
