<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes\Schemas;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Docencia\Data\DatosDeDocente;
use App\Modules\Docencia\Data\IdiomaHabilitado;
use App\Modules\Docencia\Models\CondicionLaboral;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Models\DocenteIdioma;
use App\Modules\Docencia\Models\GradoAcademico;
use App\Modules\Personas\Contracts\BusquedaDePersonas;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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
                            ->helperText('Registra antes a la persona en «Personas»; aquí se busca por documento o por apellidos y nombres.')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => app(BusquedaDePersonas::class)($search))
                            ->getOptionLabelUsing(fn (int|string $value): ?string => app(EtiquetasDePersonas::class)([(int) $value])[(int) $value] ?? null)
                            ->disabledOn('edit')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('correo_institucional')->label('Correo institucional')->email()->required()->maxLength(120),
                        Select::make('condicion_laboral_id')->label('Condición laboral')->options(fn (?Docente $record): array => self::opciones(CondicionLaboral::query(), $record?->condicion_laboral_id))->required(),
                        Select::make('grado_academico_id')->label('Grado académico')->options(fn (?Docente $record): array => self::opciones(GradoAcademico::query(), $record?->grado_academico_id))->placeholder('Sin registrar')->helperText('Define el costo por hora.'),
                    ]),
                Section::make('Datos para el expediente')
                    ->columns(3)
                    ->schema([
                        TextInput::make('ruc')->label('RUC')->required()->length(11),
                        TextInput::make('banco')->label('Banco')->required()->maxLength(80)->datalist(fn (): array => self::bancos()),
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
            'condicion_laboral_id' => $docente->condicion_laboral_id,
            'grado_academico_id' => $docente->grado_academico_id,
            'idiomas' => $docente->idiomas()->orderBy('id')->get()
                ->map(fn (DocenteIdioma $idioma): array => ['idioma_id' => $idioma->idioma_id, 'nivel_maximo' => $idioma->nivel_maximo->value])
                ->all(),
        ];
    }

    /**
     * Active items, plus the inactive one the teacher already has so editing keeps it.
     *
     * @param  Builder<CondicionLaboral>|Builder<GradoAcademico>  $catalogo
     * @return array<int, string>
     */
    private static function opciones(Builder $catalogo, ?int $actual): array
    {
        return $catalogo->where(fn (Builder $consulta): Builder => $consulta->where('activo', true)->when($actual !== null, fn (Builder $o): Builder => $o->orWhereKey($actual)))
            ->orderBy('orden')->orderBy('nombre')->pluck('nombre', 'id')->all();
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
            condicionLaboralId: self::entero($data['condicion_laboral_id'] ?? null),
            gradoAcademicoId: is_numeric($data['grado_academico_id'] ?? null) ? (int) $data['grado_academico_id'] : null,
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

    /**
     * @return list<string>
     */
    private static function bancos(): array
    {
        $bancos = config('ciln.docencia.bancos');

        return is_array($bancos) ? array_values(array_filter($bancos, is_string(...))) : [];
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
