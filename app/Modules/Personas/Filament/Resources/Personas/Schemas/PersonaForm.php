<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas\Schemas;

use App\Modules\Personas\Models\Persona;
use App\Modules\Personas\Models\TipoDocumento;
use App\Modules\Personas\Models\TipoVinculo;
use App\Modules\Personas\Support\DocumentoValido;
use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

final class PersonaForm
{
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
                            ->options(fn (?Persona $record): array => self::documentos($record?->tipo_documento))
                            ->default(fn (): ?string => TipoDocumento::query()->where('por_defecto', true)->value('codigo'))
                            ->required(),
                        TextInput::make('numero_documento')
                            ->label('Número de documento')
                            ->required()
                            ->maxLength(20)
                            ->rule(fn (Get $get, ?Persona $record): DocumentoValido => DocumentoValido::para($get('tipo_documento'), $record))
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
                        TextInput::make('correo')->label('Correo personal')->email()->maxLength(150)
                            ->helperText(fn (?Persona $record): ?string => $record?->user_id === null ? null : 'Este correo no cambia el correo con el que ingresa al portal.'),
                        TextInput::make('celular')
                            ->label('Celular')
                            ->maxLength(20)
                            ->rule(fn (): Closure => self::formatoDeCelular()),
                    ]),
                Select::make('tipo_vinculo')
                    ->label('Tipo de vínculo')
                    ->options(fn (?Persona $record): array => self::vinculos($record?->tipo_vinculo))
                    ->default(fn (): ?string => TipoVinculo::query()->where('por_defecto', true)->value('codigo'))
                    ->required(),
                Checkbox::make('acepta_tratamiento')
                    ->label('La persona aceptó el tratamiento de sus datos personales')
                    ->helperText('Márquelo solo si la persona firmó su consentimiento conforme a la Ley N.° 29733.')
                    ->visible(fn (?Persona $record): bool => $record?->tratamiento_aceptado_en === null),
            ]);
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
