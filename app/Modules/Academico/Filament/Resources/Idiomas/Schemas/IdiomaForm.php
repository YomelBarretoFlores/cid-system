<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Idiomas\Schemas;

use App\Modules\Academico\Models\Idioma;
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
                    ->rule(fn (?Idioma $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                        if (self::codigoEnUso((string) $value, $record)) {
                            $fail(self::CODIGO_REPETIDO);
                        }
                    })
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
        $mensaje = $error->getMessage();

        return match (true) {
            str_contains($mensaje, 'aca_idiomas_codigo_unique') => ValidationException::withMessages(['data.codigo' => self::CODIGO_REPETIDO]),
            str_contains($mensaje, 'aca_idiomas_nombre_unique') => ValidationException::withMessages(['data.nombre' => self::NOMBRE_REPETIDO]),
            default => $error,
        };
    }

    private static function codigoEnUso(string $codigo, ?Idioma $actual): bool
    {
        $consulta = Idioma::query()->where('codigo', Idioma::normalizarCodigo($codigo));

        if ($actual instanceof Idioma) {
            $consulta->whereKeyNot($actual->getKey());
        }

        return $consulta->exists();
    }
}
