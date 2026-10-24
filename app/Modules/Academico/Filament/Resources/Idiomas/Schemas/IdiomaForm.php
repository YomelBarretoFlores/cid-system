<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Idiomas\Schemas;

use App\Modules\Academico\Models\Idioma;
use App\Shared\Support\Database\RestriccionUnica;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class IdiomaForm
{
    public const string CODIGO_REPETIDO = 'Ya existe un idioma con ese código.';

    public const string NOMBRE_REPETIDO = 'Ya existe un idioma con ese nombre.';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('codigo')
                    ->label('Código')
                    ->required()
                    ->maxLength(10)
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        if (Idioma::query()->where('codigo', Idioma::normalizarCodigo((string) $value))->exists()) {
                            $fail(self::CODIGO_REPETIDO);
                        }
                    }, condition: fn (string $operation): bool => $operation === 'create')
                    ->disabledOn('edit')
                    ->helperText('No cambia después de crear el idioma.'),
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(80)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => self::NOMBRE_REPETIDO]),
                Toggle::make('es_lengua_nativa')
                    ->label('Lengua originaria')
                    ->default(false),
                Toggle::make('activo')
                    ->label('Disponible para nuevos grupos')
                    ->default(true),
            ]);
    }

    public static function errorDeDuplicado(UniqueConstraintViolationException $error): ValidationException|UniqueConstraintViolationException
    {
        return match (true) {
            RestriccionUnica::es($error, 'aca_idiomas_codigo_unique') => ValidationException::withMessages(['data.codigo' => self::CODIGO_REPETIDO]),
            RestriccionUnica::es($error, 'aca_idiomas_nombre_unique') => ValidationException::withMessages(['data.nombre' => self::NOMBRE_REPETIDO]),
            default => $error,
        };
    }
}
