<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Filament\Concerns;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\CatalogoDePeriodos;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Tablero\Actions\ExportarReporteGeneralExcel;
use App\Modules\Tablero\Actions\ExportarReporteGeneralPdf;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Filament\Tables\SeccionDeReporteTable;
use App\Modules\Tablero\Support\GruposDelPeriodo;
use App\Modules\Tablero\Support\PeriodoPorDefecto;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Support\Filament\DescargaDePdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Filters, on-screen table and PDF/Excel downloads shared by the general reports; each page only says how to build its report.
trait ReporteGeneralDelPanel
{
    use InteractsWithTable;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    // The section shown in the table; the PDF and the Excel always carry every section.
    public int $seccion = 0;

    private ReporteGeneral|string|null $resultado = null;

    abstract protected function generar(FiltrosDeReporte $filtros): ReporteGeneral;

    public function mount(): void
    {
        $this->getSchema('form')?->fill(['periodo_id' => PeriodoPorDefecto::id()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->statePath('data')
            ->components([
                Select::make('periodo_id')->label('Periodo académico')->options(fn (): array => app(CatalogoDePeriodos::class)())->required()->selectablePlaceholder(false)->live()
                    ->afterStateUpdated(fn (Set $set): mixed => $set('grupo_id', null)),
                Select::make('idioma_id')->label('Idioma')->placeholder('Todos los idiomas')
                    ->options(fn (): array => collect(app(CatalogoDeIdiomas::class)())->mapWithKeys(fn (IdiomaDelCatalogo $idioma): array => [$idioma->id => $idioma->nombre])->all())->live()
                    ->afterStateUpdated(fn (Set $set): mixed => $set('grupo_id', null)),
                Select::make('nivel')->label('Nivel')->placeholder('Todos los niveles')
                    ->options(collect(NivelCurricular::cases())->mapWithKeys(fn (NivelCurricular $nivel): array => [$nivel->value => $nivel->etiqueta()])->all())->live()
                    ->afterStateUpdated(fn (Set $set): mixed => $set('grupo_id', null)),
                Select::make('grupo_id')->label('Grupo')->placeholder('Todos los grupos')->searchable()->live()
                    ->options(fn (): array => $this->gruposElegibles()),
            ]);
    }

    // The view shows either the report or the business reason it cannot be built; the table reads the same result.
    public function reporte(): ReporteGeneral|string
    {
        if ($this->resultado !== null) {
            return $this->resultado;
        }

        try {
            return $this->resultado = $this->generar($this->filtros());
        } catch (ReglaDeNegocioIncumplida $error) {
            return $this->resultado = $error->getMessage();
        }
    }

    public function table(Table $table): Table
    {
        $reporte = $this->reporte();

        return SeccionDeReporteTable::configure($table, $reporte instanceof ReporteGeneral ? ($reporte->secciones[$this->seccion] ?? null) : null);
    }

    public function updatedData(): void
    {
        $this->resultado = null;
        $this->tableSearch = '';
        $reporte = $this->reporte();

        if (! $reporte instanceof ReporteGeneral || ! isset($reporte->secciones[$this->seccion])) {
            $this->seccion = 0;
        }

        $this->reconstruirTabla();
    }

    public function updatedSeccion(): void
    {
        $this->tableSearch = '';
        $this->tableSort = null;
        $this->reconstruirTabla();
    }

    // The table is built before Livewire applies the request's changes, so a new section or new filters need it built again.
    private function reconstruirTabla(): void
    {
        $this->table = $this->table($this->makeTable());
        $this->resetPage();
    }

    public function getView(): string
    {
        return 'tablero::filament.reporte-general';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('descargarPdf')
                ->label('Exportar PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->authorize(fn (): bool => static::canAccess())
                ->action(fn (Action $action): ?StreamedResponse => $this->exportar($action, fn (ReporteGeneral $reporte): StreamedResponse => DescargaDePdf::desde(app(ExportarReporteGeneralPdf::class)($reporte)))),
            Action::make('descargarExcel')
                ->label('Excel')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('gray')
                ->authorize(fn (): bool => static::canAccess())
                ->action(fn (Action $action): ?BinaryFileResponse => $this->exportar($action, app(ExportarReporteGeneralExcel::class))),
        ];
    }

    /**
     * @template TRespuesta
     *
     * @param  callable(ReporteGeneral): TRespuesta  $exportador
     * @return TRespuesta|null
     */
    private function exportar(Action $action, callable $exportador): mixed
    {
        try {
            return $exportador($this->generar($this->filtros()));
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);
            $action->halt();

            return null;
        }
    }

    /**
     * The groups that match the chosen period, language and level.
     *
     * @return array<int, string>
     */
    private function gruposElegibles(): array
    {
        try {
            $filtros = $this->filtros();
        } catch (ReglaDeNegocioIncumplida) {
            return [];
        }

        return GruposDelPeriodo::consulta($filtros->delTablero())->orderBy('g.codigo')->pluck('g.codigo', 'g.id')
            ->mapWithKeys(fn (mixed $codigo, mixed $id): array => [(int) $id => (string) $codigo])->all();
    }

    private function filtros(): FiltrosDeReporte
    {
        $datos = $this->data ?? [];

        return new FiltrosDeReporte(
            periodoId: is_numeric($datos['periodo_id'] ?? null) ? (int) $datos['periodo_id'] : throw new ReglaDeNegocioIncumplida('Elige un periodo válido.'),
            idiomaId: is_numeric($datos['idioma_id'] ?? null) ? (int) $datos['idioma_id'] : null,
            nivel: $this->nivel($datos['nivel'] ?? null),
            grupoId: is_numeric($datos['grupo_id'] ?? null) ? (int) $datos['grupo_id'] : null,
        );
    }

    private function nivel(mixed $valor): ?NivelCurricular
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return is_string($valor) && NivelCurricular::tryFrom($valor) instanceof NivelCurricular
            ? NivelCurricular::from($valor)
            : throw new ReglaDeNegocioIncumplida('Elige un nivel válido.');
    }
}
