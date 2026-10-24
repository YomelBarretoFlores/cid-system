<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Pagos\Schemas;

use App\Modules\Pagos\Enums\MedioPago;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Personas\Contracts\BusquedaDePersonas;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                    ->helperText('Busque por número de documento o por apellidos y nombres.')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => app(BusquedaDePersonas::class)($search))
                    ->getOptionLabelUsing(fn (int|string $value): ?string => app(EtiquetasDePersonas::class)([(int) $value])[(int) $value] ?? null)
                    ->required()
                    ->columnSpanFull(),
                Select::make('concepto_id')
                    ->label('Concepto TUPA/TUSNE')
                    ->options(fn (): array => Concepto::query()->where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all())
                    ->required(),
                Select::make('medio_pago')
                    ->label('Medio de pago')
                    ->options(collect(MedioPago::cases())->mapWithKeys(fn (MedioPago $medio): array => [$medio->value => $medio->etiqueta()])->all())
                    ->default(MedioPago::CajaPresencial->value)
                    ->required(),
                TextInput::make('numero_operacion')->label('N.° de recibo u operación')->required()->maxLength(40),
                DatePicker::make('fecha_pago')
                    ->label('Fecha de pago')
                    ->required()
                    ->maxDate(fn (): string => CarbonImmutable::today('America/Lima')->toDateString())
                    ->default(fn (): string => CarbonImmutable::today('America/Lima')->toDateString()),
                TextInput::make('monto')->label('Monto')->prefix('S/')->numeric()->minValue(0.01)->maxValue(99999999.99)->required(),
                FileUpload::make('recibo')
                    ->label('Recibo escaneado (JPG, PNG o PDF)')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                    ->maxSize(self::RECIBO_MAXIMO_KB)
                    ->storeFiles(false)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
