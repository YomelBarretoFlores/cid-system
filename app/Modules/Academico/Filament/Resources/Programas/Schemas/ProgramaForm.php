<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Programas\Schemas;

use App\Modules\Academico\Enums\RegimenDePrograma;
use App\Modules\Academico\Models\Programa;
use App\Shared\Support\Database\RestriccionUnica;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

final class ProgramaForm
{
    public const string CODIGO_REPETIDO = 'Ya existe un programa con ese código.';

    public const string NOMBRE_REPETIDO = 'Ya existe un programa con ese nombre.';

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
                        if (Programa::query()->where('codigo', Programa::normalizarCodigo((string) $value))->exists()) {
                            $fail(self::CODIGO_REPETIDO);
                        }
                    }, condition: fn (string $operation): bool => $operation === 'create')
                    ->disabledOn('edit')
                    ->helperText('No cambia después de crear el programa.'),
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(80)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => self::NOMBRE_REPETIDO]),
                Select::make('regimen')
                    ->label('Régimen de ciclo')
                    ->options(RegimenDePrograma::opciones())
                    ->required()
                    ->helperText('Decide si los grupos del programa pueden ser regulares, acelerados o de ambos tipos.'),
                Toggle::make('activo')
                    ->label('Disponible para nuevos grupos')
                    ->default(true),
            ]);
    }

    public static function errorDeDuplicado(UniqueConstraintViolationException $error): ValidationException|UniqueConstraintViolationException
    {
        return match (true) {
            RestriccionUnica::es($error, 'aca_programas_codigo_unique') => ValidationException::withMessages(['data.codigo' => self::CODIGO_REPETIDO]),
            RestriccionUnica::es($error, 'aca_programas_nombre_unique') => ValidationException::withMessages(['data.nombre' => self::NOMBRE_REPETIDO]),
            default => $error,
        };
    }
}
