<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Conceptos\Schemas;

use App\Modules\Pagos\Enums\FuenteConcepto;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class ConceptoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('codigo')
                    ->label('Código TUPA/TUSNE')
                    ->required()
                    ->maxLength(20)
                    ->dehydrateStateUsing(fn (string $state): string => mb_strtoupper(trim($state)))
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        if (Concepto::query()->where('codigo', mb_strtoupper(trim((string) $value)))->exists()) {
                            $fail('Ya existe un concepto con ese código.');
                        }
                    }, condition: fn (string $operation): bool => $operation === 'create')
                    ->disabledOn('edit')
                    ->helperText('No cambia después de crear el concepto.'),
                TextInput::make('nombre')->label('Nombre')->required()->maxLength(150),
                Select::make('tipo')
                    ->label('Servicio que habilita')
                    ->options(collect(TipoConcepto::cases())->mapWithKeys(fn (TipoConcepto $tipo): array => [$tipo->value => $tipo->etiqueta()])->all())
                    ->required(),
                Select::make('fuente')
                    ->label('Fuente')
                    ->options(collect(FuenteConcepto::cases())->mapWithKeys(fn (FuenteConcepto $fuente): array => [$fuente->value => $fuente->etiqueta()])->all())
                    ->required(),
                Toggle::make('activo')
                    ->label('Disponible para nuevos pagos')
                    ->default(true),
            ]);
    }
}
