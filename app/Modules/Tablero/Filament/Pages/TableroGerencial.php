<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Filament\Pages;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\CatalogoDePeriodos;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Tablero\Actions\ArmarTablero;
use App\Modules\Tablero\Actions\ExportarTableroExcel;
use App\Modules\Tablero\Actions\ExportarTableroPdf;
use App\Modules\Tablero\Data\FiltrosDelTablero;
use App\Modules\Tablero\Data\Tablero;
use App\Modules\Tablero\Enums\PermisoTablero;
use App\Modules\Tablero\Support\PeriodoPorDefecto;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Support\Filament\DescargaDePdf;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TableroGerencial extends Page
{
    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    #[\Override]
    protected static ?int $navigationSort = -10;

    #[\Override]
    protected static ?string $navigationLabel = 'Tablero gerencial';

    #[\Override]
    protected static ?string $title = 'Tablero gerencial';

    #[\Override]
    protected static ?string $slug = 'tablero-gerencial';

    #[\Override]
    protected ?string $subheading = 'Indicadores financieros y académicos con los datos registrados hasta este momento.';

    #[\Override]
    protected string $view = 'tablero::filament.tablero-gerencial';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoTablero::Ver->value) ?? false;
    }

    public function mount(): void
    {
        $this->getSchema('form')?->fill(['periodo_id' => PeriodoPorDefecto::id()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->statePath('data')
            ->components([
                Select::make('periodo_id')->label('Periodo académico')->options(fn (): array => app(CatalogoDePeriodos::class)())->required()->selectablePlaceholder(false)->live(),
                Select::make('idioma_id')->label('Idioma')->placeholder('Todos los idiomas')
                    ->options(fn (): array => collect(app(CatalogoDeIdiomas::class)())->mapWithKeys(fn (IdiomaDelCatalogo $idioma): array => [$idioma->id => $idioma->nombre])->all())->live(),
                Select::make('nivel')->label('Nivel')->placeholder('Todos los niveles')
                    ->options(collect(NivelCurricular::cases())->mapWithKeys(fn (NivelCurricular $nivel): array => [$nivel->value => $nivel->etiqueta()])->all())->live(),
            ]);
    }

    // The view shows either the dashboard or the business reason it cannot be built.
    public function tablero(): Tablero|string
    {
        try {
            return app(ArmarTablero::class)($this->filtros());
        } catch (ReglaDeNegocioIncumplida $error) {
            return $error->getMessage();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('descargarPdf')
                ->label('Exportar PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->authorize(fn (): bool => self::canAccess())
                ->action(fn (Action $action): ?StreamedResponse => $this->exportar($action, fn (Tablero $tablero): StreamedResponse => DescargaDePdf::desde(app(ExportarTableroPdf::class)($tablero)))),
            Action::make('descargarExcel')
                ->label('Excel')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('gray')
                ->authorize(fn (): bool => self::canAccess())
                ->action(fn (Action $action): ?BinaryFileResponse => $this->exportar($action, app(ExportarTableroExcel::class))),
        ];
    }

    /**
     * @template TRespuesta
     *
     * @param  callable(Tablero): TRespuesta  $exportador
     * @return TRespuesta|null
     */
    private function exportar(Action $action, callable $exportador): mixed
    {
        try {
            return $exportador(app(ArmarTablero::class)($this->filtros()));
        } catch (ReglaDeNegocioIncumplida $error) {
            AccionDeNegocio::notificarError($error);
            $action->halt();

            return null;
        }
    }

    private function filtros(): FiltrosDelTablero
    {
        $datos = $this->data ?? [];

        return new FiltrosDelTablero(
            periodoId: is_numeric($datos['periodo_id'] ?? null) ? (int) $datos['periodo_id'] : throw new ReglaDeNegocioIncumplida('Elige un periodo válido.'),
            idiomaId: is_numeric($datos['idioma_id'] ?? null) ? (int) $datos['idioma_id'] : null,
            nivel: $this->nivel($datos['nivel'] ?? null),
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
