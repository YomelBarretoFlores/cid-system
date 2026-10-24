<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Pages;

use App\Modules\Docencia\Actions\ConsolidarHorasDictadas;
use App\Modules\Docencia\Actions\ExportarExpedienteDeHorasPdf;
use App\Modules\Docencia\Actions\ExportarExpedientesDeHorasCsv;
use App\Modules\Docencia\Data\ExpedienteDeHoras;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Support\Logging\RegistroDeRechazos;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Spatie\LaravelPdf\PdfBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

final class ExpedienteDeHorasDocentes extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Docencia';

    protected static ?string $navigationLabel = 'Expediente de horas';

    protected static ?string $title = 'Expediente de horas por docente';

    protected static ?string $slug = 'expediente-de-horas';

    protected string $view = 'docencia::filament.expediente-de-horas';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoDocencia::Reportar->value) ?? false;
    }

    public function mount(): void
    {
        $hoy = CarbonImmutable::today('America/Lima');

        $this->getSchema('form')?->fill(['anio' => $hoy->year, 'mes' => $hoy->month]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->statePath('data')
            ->components([
                Select::make('mes')->label('Mes')->options(ExpedienteDeHoras::meses())->required()->live(),
                TextInput::make('anio')->label('Año')->numeric()->minValue(2000)->maxValue(2100)->required()->live(debounce: 500),
            ]);
    }

    // The view shows either the reports of the month or the business reason they cannot be built.
    /**
     * @return list<ExpedienteDeHoras>|string
     */
    public function resultado(): array|string
    {
        try {
            return $this->expedientes();
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);

            return $error->getMessage();
        }
    }

    public function periodo(): string
    {
        [$anio, $mes] = $this->mesElegido();

        $nombre = ExpedienteDeHoras::nombreDelMes($mes);

        return $nombre === null ? '' : "{$nombre} {$anio}";
    }

    public function descargarPdfAction(): Action
    {
        return Action::make('descargarPdf')
            ->label('PDF')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->link()
            ->authorize(fn (): bool => self::canAccess())
            ->action(fn (Action $action, array $arguments): ?PdfBuilder => $this->exportar(
                $action,
                fn (): PdfBuilder => app(ExportarExpedienteDeHorasPdf::class)($this->expedienteDe($arguments['docente'] ?? null)),
            ));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('descargarCsv')
                ->label('Descargar CSV del mes')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('gray')
                ->authorize(fn (): bool => self::canAccess())
                ->action(fn (Action $action): ?StreamedResponse => $this->exportar($action, function (): StreamedResponse {
                    [$anio, $mes] = $this->mesElegido();

                    return app(ExportarExpedientesDeHorasCsv::class)($this->expedientes(), ExpedienteDeHoras::nombreDeArchivoDelMes($anio, $mes, 'csv'));
                })),
        ];
    }

    /**
     * @template TRespuesta
     *
     * @param  callable(): TRespuesta  $exportador
     * @return TRespuesta|null
     */
    private function exportar(Action $action, callable $exportador): mixed
    {
        try {
            return $exportador();
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);
            $action->halt();

            return null;
        }
    }

    /**
     * @return list<ExpedienteDeHoras>
     */
    private function expedientes(?int $docenteId = null): array
    {
        [$anio, $mes] = $this->mesElegido();
        $expedientes = app(ConsolidarHorasDictadas::class)($anio, $mes, $docenteId);

        // Teachers without classes in the month are left out of the screen, the PDF and the CSV.
        return array_values(array_filter($expedientes, fn (ExpedienteDeHoras $expediente): bool => $expediente->lineas !== []));
    }

    private function expedienteDe(mixed $docente): ExpedienteDeHoras
    {
        $docenteId = is_int($docente) || is_string($docente) ? filter_var($docente, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;

        return ($docenteId === false ? null : ($this->expedientes($docenteId)[0] ?? null))
            ?? throw new ReglaDeNegocioIncumplida('El docente no tiene horas dictadas en este mes.');
    }

    /**
     * @return array{int, int}
     */
    private function mesElegido(): array
    {
        $datos = $this->data ?? [];

        return [
            is_numeric($datos['anio'] ?? null) ? (int) $datos['anio'] : 0,
            is_numeric($datos['mes'] ?? null) ? (int) $datos['mes'] : 0,
        ];
    }
}
