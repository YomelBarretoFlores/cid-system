<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Pagos\Schemas;

use App\Modules\Academico\Contracts\CatalogoDeProgramas;
use App\Modules\Academico\Data\ProgramaDelCatalogo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Pagos\Filament\Schemas\CampoDeMonto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\MedioDePago;
use App\Modules\Pagos\Support\TarifaAplicable;
use App\Modules\Personas\Contracts\BusquedaDePersonas;
use App\Modules\Personas\Contracts\ConsultaDeVinculo;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

final class PagoForm
{
    public const int RECIBO_MAXIMO_KB = 5120;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('persona_id')
                    ->label('Persona')
                    ->helperText('Busca por número de documento o por apellidos y nombres.')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => app(BusquedaDePersonas::class)($search))
                    ->getOptionLabelUsing(fn (int|string $value): ?string => app(EtiquetasDePersonas::class)([(int) $value])[(int) $value] ?? null)
                    ->live()
                    ->required()
                    ->columnSpanFull(),
                Select::make('concepto_id')
                    ->label('Concepto TUPA/TUSNE')
                    ->options(fn (): array => Concepto::query()->where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all())
                    ->live()
                    ->required(),
                Select::make('nivel')
                    ->label('Nivel pagado')
                    ->options(array_column(array_map(fn (NivelCurricular $nivel): array => [$nivel->value, $nivel->etiqueta()], NivelCurricular::cases()), 1, 0))
                    ->visible(fn (Get $get): bool => self::criterios($get)['nivel'])
                    ->required(fn (Get $get): bool => self::criterios($get)['nivel']),
                Select::make('programa_id')
                    ->label('Programa')
                    ->placeholder('Ninguno en particular')
                    ->helperText('Solo si el pago es la tarifa de un programa, por ejemplo Posgrado.')
                    ->options(fn (): array => self::programasActivos())
                    ->visible(fn (Get $get): bool => self::criterios($get)['programa']),
                Select::make('medio_pago_id')
                    ->label('Medio de pago')
                    ->options(fn (): array => MedioDePago::query()->activos()->pluck('nombre', 'id')->all())
                    ->default(fn (): ?int => MedioDePago::query()->activos()->value('id'))
                    ->required(),
                TextInput::make('numero_operacion')->label('N.° de recibo u operación')->required()->maxLength(40),
                DatePicker::make('fecha_pago')
                    ->label('Fecha de pago')
                    ->live()
                    ->required()
                    ->maxDate(fn (): string => CarbonImmutable::today('America/Lima')->toDateString())
                    ->default(fn (): string => CarbonImmutable::today('America/Lima')->toDateString()),
                CampoDeMonto::make(),
                FileUpload::make('recibo')
                    ->label('Recibo escaneado (JPG, PNG o PDF)')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                    ->maxSize(self::RECIBO_MAXIMO_KB)
                    ->storeFiles(false)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array{nivel: bool, programa: bool}
     */
    private static function criterios(Get $get): array
    {
        $concepto = $get('concepto_id');
        $persona = $get('persona_id');
        $fecha = $get('fecha_pago');

        if (! is_numeric($concepto) || ! is_numeric($persona)) {
            return ['nivel' => false, 'programa' => false];
        }

        $dia = is_string($fecha) && $fecha !== '' ? CarbonImmutable::parse($fecha) : CarbonImmutable::today('America/Lima');

        return TarifaAplicable::criterios((int) $concepto, app(ConsultaDeVinculo::class)((int) $persona), $dia);
    }

    /**
     * @return array<int, string>
     */
    private static function programasActivos(): array
    {
        $activos = array_filter(app(CatalogoDeProgramas::class)(), fn (ProgramaDelCatalogo $programa): bool => $programa->activo);

        return array_column(array_map(fn (ProgramaDelCatalogo $programa): array => [$programa->id, $programa->nombre], $activos), 1, 0);
    }
}
