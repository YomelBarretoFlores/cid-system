<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Conceptos\Schemas;

use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\FuenteDeConcepto;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

final class ConceptoForm
{
    public const string CODIGO_REPETIDO = 'Ya existe un concepto con ese código.';

    /**
     * Active sources, plus the inactive one a concept already uses so editing it keeps its value.
     *
     * @return array<int, string>
     */
    private static function fuentes(?Concepto $concepto): array
    {
        return FuenteDeConcepto::query()
            ->where(fn (Builder $consulta): Builder => $consulta->where('activo', true)->when($concepto instanceof Concepto, fn (Builder $actual): Builder => $actual->orWhereKey($concepto?->fuente_id)))
            ->orderBy('orden')
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->all();
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('codigo')
                    ->label('Código TUPA/TUSNE')
                    ->required()
                    ->maxLength(20)
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        if (Concepto::query()->where('codigo', Concepto::normalizarCodigo((string) $value))->exists()) {
                            $fail(self::CODIGO_REPETIDO);
                        }
                    }, condition: fn (string $operation): bool => $operation === 'create')
                    ->disabledOn('edit')
                    ->helperText('No cambia después de crear el concepto.'),
                TextInput::make('nombre')->label('Nombre')->required()->maxLength(150),
                Select::make('tipo')
                    ->label('Servicio que habilita')
                    ->options(collect(TipoConcepto::cases())->mapWithKeys(fn (TipoConcepto $tipo): array => [$tipo->value => $tipo->etiqueta()])->all())
                    ->required(),
                Select::make('fuente_id')
                    ->label('Fuente')
                    ->options(fn (?Concepto $record): array => self::fuentes($record))
                    ->required(),
                Toggle::make('activo')
                    ->label('Disponible para nuevos pagos')
                    ->default(true),
            ]);
    }
}
