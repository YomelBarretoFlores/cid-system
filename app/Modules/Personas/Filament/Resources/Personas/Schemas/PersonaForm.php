<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas\Schemas;

use App\Modules\Personas\Enums\TipoDocumento;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Models\Persona;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

final class PersonaForm
{
    public const string DNI_INVALIDO = 'El DNI debe tener exactamente 8 dígitos.';

    public const string DOCUMENTO_INVALIDO = 'El documento debe tener de 6 a 12 letras o números, sin espacios ni guiones.';

    public const string DOCUMENTO_REPETIDO = 'Ya existe una persona registrada con ese documento.';

    public const string CELULAR_INVALIDO = 'El celular debe tener de 7 a 15 dígitos y puede empezar con +.';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Documento de identidad')
                    ->columns(2)
                    ->schema([
                        Select::make('tipo_documento')
                            ->label('Tipo de documento')
                            ->options(self::opciones(TipoDocumento::cases()))
                            ->default(TipoDocumento::Dni->value)
                            ->required(),
                        TextInput::make('numero_documento')
                            ->label('Número de documento')
                            ->required()
                            ->maxLength(20)
                            ->rule(fn (Get $get): Closure => self::formatoDeDocumento($get('tipo_documento')))
                            ->rule(fn (Get $get, ?Persona $record): Closure => self::documentoSinRepetir($get('tipo_documento'), $record)),
                    ]),
                Section::make('Datos personales')
                    ->columns(3)
                    ->schema([
                        TextInput::make('apellido_paterno')->label('Apellido paterno')->required()->maxLength(60),
                        TextInput::make('apellido_materno')->label('Apellido materno')->maxLength(60),
                        TextInput::make('nombres')->label('Nombres')->required()->maxLength(80),
                        DatePicker::make('fecha_nacimiento')
                            ->label('Fecha de nacimiento')
                            ->after('1900-01-01')
                            ->before(fn (): string => today('America/Lima')->toDateString()),
                        TextInput::make('correo')->label('Correo personal')->email()->maxLength(150),
                        TextInput::make('celular')
                            ->label('Celular')
                            ->maxLength(20)
                            ->rule(fn (): Closure => self::formatoDeCelular()),
                    ]),
                Select::make('tipo_vinculo')
                    ->label('Tipo de vínculo')
                    ->options(self::opciones(TipoVinculo::cases()))
                    ->default(TipoVinculo::PublicoGeneral->value)
                    ->required(),
            ]);
    }

    /**
     * @param  list<TipoDocumento|TipoVinculo>  $casos
     * @return array<string, string>
     */
    public static function opciones(array $casos): array
    {
        return collect($casos)->mapWithKeys(fn (TipoDocumento|TipoVinculo $caso): array => [$caso->value => $caso->etiqueta()])->all();
    }

    private static function formatoDeDocumento(mixed $tipo): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($tipo): void {
            $esDni = $tipo === TipoDocumento::Dni->value;

            if (! preg_match($esDni ? '/^[0-9]{8}$/' : '/^[A-Z0-9]{6,12}$/', Persona::normalizarDocumento((string) $value))) {
                $fail($esDni ? self::DNI_INVALIDO : self::DOCUMENTO_INVALIDO);
            }
        };
    }

    private static function documentoSinRepetir(mixed $tipo, ?Persona $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($tipo, $record): void {
            $repetido = Persona::query()
                ->where('tipo_documento', $tipo)
                ->where('numero_documento', Persona::normalizarDocumento((string) $value))
                ->when($record, fn ($query, Persona $actual) => $query->whereKeyNot($actual->getKey()))
                ->exists();

            if ($repetido) {
                $fail(self::DOCUMENTO_REPETIDO);
            }
        };
    }

    private static function formatoDeCelular(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! preg_match('/^\+?[0-9]{7,15}$/', Persona::normalizarCelular((string) $value))) {
                $fail(self::CELULAR_INVALIDO);
            }
        };
    }
}
