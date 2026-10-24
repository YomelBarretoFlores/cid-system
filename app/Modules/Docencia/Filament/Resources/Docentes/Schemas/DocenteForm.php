<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes\Schemas;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Docencia\Data\DatosDeDocente;
use App\Modules\Docencia\Data\IdiomaHabilitado;
use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Models\DocenteIdioma;
use App\Modules\Personas\Contracts\BusquedaDePersonas;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class DocenteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Docente')
                    ->columns(2)
                    ->schema([
                        Select::make('persona_id')
                            ->label('Persona')
                            ->helperText('Registre antes a la persona en «Personas»; aquí se busca por documento o por apellidos y nombres.')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => app(BusquedaDePersonas::class)($search))
                            ->getOptionLabelUsing(fn (int|string $value): ?string => app(EtiquetasDePersonas::class)([(int) $value])[(int) $value] ?? null)
                            ->disabledOn('edit')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('correo_institucional')->label('Correo institucional')->email()->required()->maxLength(120),
                        Select::make('condicion_laboral')->label('Condición laboral')->options(CondicionLaboral::opciones())->required(),
                    ]),
                Section::make('Datos para el expediente')
                    ->columns(3)
                    ->schema([
                        TextInput::make('ruc')->label('RUC')->required()->length(11),
                        TextInput::make('banco')->label('Banco')->required()->maxLength(80),
                        TextInput::make('cuenta_bancaria')->label('Número de cuenta')->required()->maxLength(30)->password()->revealable(),
                    ]),
                Section::make('Perfil académico')
                    ->schema([
                        Repeater::make('idiomas')
                            ->label('Idiomas habilitados para dictar')
                            ->addActionLabel('Habilitar otro idioma')
                            ->columns(2)
                            ->minItems(1)
                            ->schema([
                                Select::make('idioma_id')->label('Idioma')->options(fn (): array => self::idiomas())->distinct()->required(),
                                Select::make('nivel_maximo')->label('Nivel máximo autorizado')->options(self::niveles())->required(),
                            ]),
                    ]),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function estado(Docente $docente): array
    {
        return [
            ...$docente->only(['persona_id', 'correo_institucional', 'ruc', 'banco', 'cuenta_bancaria']),
            'condicion_laboral' => $docente->condicion_laboral->value,
            'idiomas' => $docente->idiomas()->orderBy('id')->get()
                ->map(fn (DocenteIdioma $idioma): array => ['idioma_id' => $idioma->idioma_id, 'nivel_maximo' => $idioma->nivel_maximo->value])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function datos(array $data): DatosDeDocente
    {
        $idiomas = is_array($data['idiomas'] ?? null) ? $data['idiomas'] : [];

        return new DatosDeDocente(
            personaId: self::entero($data['persona_id'] ?? null),
            correoInstitucional: self::texto($data['correo_institucional'] ?? null),
            ruc: trim(self::texto($data['ruc'] ?? null)),
            banco: self::texto($data['banco'] ?? null),
            cuentaBancaria: self::texto($data['cuenta_bancaria'] ?? null),
            condicion: CondicionLaboral::from(self::texto($data['condicion_laboral'] ?? null)),
            idiomas: array_values(array_map(fn (mixed $fila): IdiomaHabilitado => new IdiomaHabilitado(
                self::entero(is_array($fila) ? ($fila['idioma_id'] ?? null) : null),
                NivelCurricular::from(self::texto(is_array($fila) ? ($fila['nivel_maximo'] ?? null) : null)),
            ), $idiomas)),
        );
    }

    /**
     * @return array<int, string>
     */
    private static function idiomas(): array
    {
        return array_column(array_map(fn (IdiomaDelCatalogo $idioma): array => [$idioma->id, $idioma->nombre], app(CatalogoDeIdiomas::class)()), 1, 0);
    }

    /**
     * @return array<string, string>
     */
    private static function niveles(): array
    {
        return array_column(array_map(fn (NivelCurricular $nivel): array => [$nivel->value, $nivel->etiqueta()], NivelCurricular::cases()), 1, 0);
    }

    private static function entero(mixed $valor): int
    {
        return is_numeric($valor) ? (int) $valor : 0;
    }

    private static function texto(mixed $valor): string
    {
        return is_string($valor) ? $valor : '';
    }
}
