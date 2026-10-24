<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Pages;

use App\Modules\Academico\Contracts\CatalogoDePeriodos;
use App\Modules\Pagos\Actions\ExportarRecaudacionCsv;
use App\Modules\Pagos\Actions\ExportarRecaudacionPdf;
use App\Modules\Pagos\Actions\GenerarReporteDeRecaudacion;
use App\Modules\Pagos\Data\FiltrosDeRecaudacion;
use App\Modules\Pagos\Data\ReporteDeRecaudacion as Reporte;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Spatie\LaravelPdf\PdfBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReporteDeRecaudacion extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static ?string $navigationLabel = 'Reporte de recaudación';

    protected static ?string $title = 'Reporte de recaudación';

    protected static ?string $slug = 'reporte-de-recaudacion';

    protected string $view = 'pagos::filament.reporte-de-recaudacion';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reportar', Pago::class) ?? false;
    }

    public function mount(): void
    {
        $hoy = CarbonImmutable::today('America/Lima');

        $this->getSchema('form')?->fill(['desde' => $hoy->startOfMonth()->toDateString(), 'hasta' => $hoy->toDateString()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->statePath('data')
            ->components([
                DatePicker::make('desde')->label('Pagos desde')->required()->live(),
                DatePicker::make('hasta')->label('Pagos hasta')->required()->live(),
                Select::make('concepto_id')->label('Concepto')->placeholder('Todos')->options(fn (): array => Concepto::query()->orderBy('codigo')->pluck('nombre', 'id')->all())->live(),
                Select::make('periodo_id')->label('Periodo académico')->placeholder('Todos')->options(fn (): array => app(CatalogoDePeriodos::class)())->live(),
            ]);
    }

    // The view shows either the report or the business reason it cannot be built.
    public function resultado(): Reporte|string
    {
        try {
            return app(GenerarReporteDeRecaudacion::class)($this->filtros());
        } catch (ReglaDeNegocioIncumplida $error) {
            return $error->getMessage();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('descargarPdf')
                ->label('Descargar PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->authorize(fn (): bool => self::canAccess())
                ->action(fn (Action $action): ?PdfBuilder => $this->exportar($action, app(ExportarRecaudacionPdf::class))),
            Action::make('descargarCsv')
                ->label('Descargar CSV')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('gray')
                ->authorize(fn (): bool => self::canAccess())
                ->action(fn (Action $action): ?StreamedResponse => $this->exportar($action, app(ExportarRecaudacionCsv::class))),
        ];
    }

    /**
     * @template TRespuesta
     *
     * @param  callable(Reporte): TRespuesta  $exportador
     * @return TRespuesta|null
     */
    private function exportar(Action $action, callable $exportador): mixed
    {
        try {
            return $exportador(app(GenerarReporteDeRecaudacion::class)($this->filtros()));
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);
            $action->halt();

            return null;
        }
    }

    private function filtros(): FiltrosDeRecaudacion
    {
        $datos = $this->data ?? [];

        return new FiltrosDeRecaudacion(
            desde: CarbonImmutable::parse($this->texto($datos['desde'] ?? null) ?: 'today'),
            hasta: CarbonImmutable::parse($this->texto($datos['hasta'] ?? null) ?: 'today'),
            conceptoId: is_numeric($datos['concepto_id'] ?? null) ? (int) $datos['concepto_id'] : null,
            periodoId: is_numeric($datos['periodo_id'] ?? null) ? (int) $datos['periodo_id'] : null,
        );
    }

    private function texto(mixed $valor): string
    {
        return is_string($valor) ? $valor : '';
    }
}
