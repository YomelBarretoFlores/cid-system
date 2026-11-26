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
                Toggle::make('por_defecto')
                    ->label('Programa por defecto')
                    ->default(false)
                    ->helperText('Se propone para grupos nuevos y se imprime en los documentos de grupos anteriores a los programas.'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{codigo: string, nombre: string, regimen: string, activo: bool, por_defecto: bool}
     */
    public static function datos(array $data, ?Programa $actual = null): array
    {
        $regimen = $data['regimen'] ?? $actual?->regimen;

        return [
            'codigo' => is_string($data['codigo'] ?? null) ? $data['codigo'] : '',
            'nombre' => is_string($data['nombre'] ?? null) ? $data['nombre'] : '',
            'regimen' => $regimen instanceof RegimenDePrograma ? $regimen->value : (is_string($regimen) ? $regimen : ''),
            'activo' => (bool) ($data['activo'] ?? $actual->activo ?? true),
            'por_defecto' => (bool) ($data['por_defecto'] ?? $actual->por_defecto ?? false),
        ];
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
