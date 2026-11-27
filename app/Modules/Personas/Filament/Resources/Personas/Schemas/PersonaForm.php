<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas\Schemas;

use App\Modules\Personas\Actions\RegistrarPersona;
use App\Modules\Personas\Data\DatosDePersona;
use App\Modules\Personas\Models\Persona;
use App\Modules\Personas\Models\TipoDocumento;
use App\Modules\Personas\Models\TipoVinculo;
use App\Modules\Personas\Support\DocumentoValido;
use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

final class PersonaForm
{
    public const string DOCUMENTO_REPETIDO = RegistrarPersona::DOCUMENTO_REPETIDO;

    public const string CELULAR_INVALIDO = RegistrarPersona::CELULAR_INVALIDO;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Documento de identidad')
                    ->columns(2)
                    ->schema(self::documento()),
                Section::make('Datos personales')
                    ->columns(3)
                    ->schema(self::personales()),
                self::vinculo(),
                self::consentimiento(),
            ]);
    }

    /**
     * @return list<Field>
     */
    public static function documento(bool $buscar = false): array
    {
        $numero = TextInput::make('numero_documento')
            ->label('Número de documento')
            ->required()
            ->maxLength(20)
            ->rule(fn (Get $get, mixed $record): DocumentoValido => DocumentoValido::para($get('tipo_documento'), self::registro($record)));

        return [
            Select::make('tipo_documento')
                ->label('Tipo de documento')
                ->options(fn (mixed $record): array => self::documentos(self::registro($record)?->tipo_documento))
                ->default(fn (): ?string => TipoDocumento::query()->where('por_defecto', true)->value('codigo'))
                ->live(condition: $buscar)
                ->required(),
            $buscar
                ? $numero->live(onBlur: true)
                : $numero->rule(fn (Get $get, mixed $record): Closure => self::documentoSinRepetir($get('tipo_documento'), self::registro($record))),
        ];
    }

    /**
     * @return list<Component>
     */
    public static function personales(): array
    {
        return [
            TextInput::make('apellido_paterno')->label('Apellido paterno')->required()->maxLength(60),
            TextInput::make('apellido_materno')->label('Apellido materno')->maxLength(60),
            TextInput::make('nombres')->label('Nombres')->required()->maxLength(80),
            DatePicker::make('fecha_nacimiento')
                ->label('Fecha de nacimiento')
                ->after('1900-01-01')
                ->before(fn (): string => today('America/Lima')->toDateString()),
            TextInput::make('correo')->label('Correo personal')->email()->maxLength(150)
                ->helperText(fn (mixed $record): ?string => self::registro($record)?->user_id === null ? null : 'Este correo no cambia el correo con el que ingresa al portal.'),
            TextInput::make('celular')
                ->label('Celular')
                ->maxLength(20)
                ->rule(fn (): Closure => self::formatoDeCelular()),
        ];
    }

    public static function vinculo(): Select
    {
        return Select::make('tipo_vinculo')
            ->label('Tipo de vínculo')
            ->options(fn (mixed $record): array => self::vinculos(self::registro($record)?->tipo_vinculo))
            ->default(fn (): ?string => TipoVinculo::query()->where('por_defecto', true)->value('codigo'))
            ->required();
    }

    public static function consentimiento(): Checkbox
    {
        return Checkbox::make('acepta_tratamiento')
            ->label('La persona aceptó el tratamiento de sus datos personales')
            ->helperText('Márquelo solo si la persona firmó su consentimiento conforme a la Ley N.° 29733.')
            ->visible(fn (mixed $record): bool => self::registro($record)?->tratamiento_aceptado_en === null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function datos(array $data): DatosDePersona
    {
        $texto = fn (string $campo): ?string => is_string($data[$campo] ?? null) ? $data[$campo] : null;

        return new DatosDePersona(
            tipoDocumento: (string) $texto('tipo_documento'),
            numeroDocumento: (string) $texto('numero_documento'),
            apellidoPaterno: (string) $texto('apellido_paterno'),
            apellidoMaterno: $texto('apellido_materno'),
            nombres: (string) $texto('nombres'),
            correo: $texto('correo'),
            celular: $texto('celular'),
            fechaNacimiento: $texto('fecha_nacimiento'),
            tipoVinculo: $texto('tipo_vinculo'),
            aceptaTratamiento: (bool) ($data['acepta_tratamiento'] ?? false),
        );
    }

    /**
     * The active document types plus the one stored, so editing a person never drops an inactive value.
     *
     * @return array<string, string>
     */
    public static function documentos(?string $guardado = null, bool $todos = false): array
    {
        return TipoDocumento::query()
            ->when(! $todos, fn (Builder $query): Builder => $query->where(fn (Builder $q): Builder => $q->where('activo', true)->orWhere('codigo', $guardado)))
            ->orderBy('orden')
            ->orderBy('nombre')
            ->pluck('nombre', 'codigo')
            ->all();
    }

    /**
     * The active vínculos plus the one stored, so editing a person never drops an inactive value.
     *
     * @return array<string, string>
     */
    public static function vinculos(?string $guardado = null, bool $todos = false): array
    {
        return TipoVinculo::query()
            ->when(! $todos, fn (Builder $query): Builder => $query->where(fn (Builder $q): Builder => $q->where('activo', true)->orWhere('codigo', $guardado)))
            ->orderBy('orden')
            ->orderBy('nombre')
            ->pluck('nombre', 'codigo')
            ->all();
    }

    // These fields also serve other modules' forms, whose record is not a person.
    private static function registro(mixed $record): ?Persona
    {
        return $record instanceof Persona ? $record : null;
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
