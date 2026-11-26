<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Idiomas\Schemas;

use App\Modules\Academico\Models\Idioma;
use App\Shared\Support\Database\RestriccionUnica;
use App\Shared\Ui\BanderaDeIdioma;
use Closure;
use Filament\Forms\Components\Select;
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
                TextInput::make('curriculo')
                    ->label('Currículo')
                    ->required()
                    ->maxLength(10)
                    ->default('01')
                    ->dehydrateStateUsing(fn (?string $state): string => trim((string) $state))
                    ->helperText('Código del plan de estudios vigente; se imprime en la constancia de notas.'),
                Select::make('bandera')
                    ->label('Bandera')
                    ->options(BanderaDeIdioma::opciones())
                    ->allowHtml()
                    ->searchable(false)
                    ->afterStateHydrated(function (Select $campo, mixed $state, ?Idioma $record): void {
                        if ($state === null && $record instanceof Idioma) {
                            $campo->state(BanderaDeIdioma::sugeridaPara($record->nombre)->value);
                        }
                    })
                    ->helperText('Se muestra junto al nombre del idioma en todo el sistema. Si no eliges una, se usa la sugerida por el nombre.'),
                Toggle::make('es_lengua_nativa')
                    ->label('Lengua originaria')
                    ->default(false),
                Toggle::make('activo')
                    ->label('Disponible para matrícula')
                    ->helperText('Si lo desactivas, sus grupos dejan de ofrecerse en la matrícula y no se pueden crear grupos nuevos.')
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
