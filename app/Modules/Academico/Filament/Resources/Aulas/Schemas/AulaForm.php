<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Aulas\Schemas;

use App\Modules\Academico\Models\Aula;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class AulaForm
{
    public const string CODIGO_REPETIDO = 'Ya existe un aula con ese código.';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('codigo')
                    ->label('Código')
                    ->required()
                    ->maxLength(20)
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        if (Aula::query()->where('codigo', Aula::normalizarCodigo((string) $value))->exists()) {
                            $fail(self::CODIGO_REPETIDO);
                        }
                    }, condition: fn (string $operation): bool => $operation === 'create')
                    ->disabledOn('edit')
                    ->helperText('No cambia después de crear el aula.'),
                TextInput::make('nombre')->label('Nombre')->required()->maxLength(80),
                TextInput::make('capacidad')->label('Capacidad (estudiantes)')->integer()->minValue(1)->maxValue(32767)->required(),
                TextInput::make('ubicacion')->label('Ubicación')->maxLength(150),
                Toggle::make('activo')
                    ->label('Disponible para nuevos grupos')
                    ->default(true),
            ]);
    }
}
