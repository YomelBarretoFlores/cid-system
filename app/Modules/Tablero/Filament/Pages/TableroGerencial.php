<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Filament\Pages;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Contracts\CatalogoDePeriodos;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Tablero\Actions\ArmarTablero;
use App\Modules\Tablero\Data\FiltrosDelTablero;
use App\Modules\Tablero\Data\Tablero;
use App\Modules\Tablero\Enums\PermisoTablero;
use App\Modules\Tablero\Support\PeriodoPorDefecto;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

final class TableroGerencial extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static ?string $navigationLabel = 'Tablero gerencial';

    protected static ?string $title = 'Tablero gerencial';

    protected static ?string $slug = 'tablero-gerencial';

    protected static ?int $navigationSort = -1;

    protected ?string $subheading = 'Indicadores financieros y académicos con los datos registrados hasta este momento.';

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

    private function filtros(): FiltrosDelTablero
    {
        $datos = $this->data ?? [];

        return new FiltrosDelTablero(
            periodoId: is_numeric($datos['periodo_id'] ?? null) ? (int) $datos['periodo_id'] : throw new ReglaDeNegocioIncumplida('Elige un periodo válido.'),
            idiomaId: is_numeric($datos['idioma_id'] ?? null) ? (int) $datos['idioma_id'] : null,
            nivel: is_string($datos['nivel'] ?? null) ? NivelCurricular::tryFrom($datos['nivel']) : null,
        );
    }
}
