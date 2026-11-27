<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Schemas;

use App\Modules\Academico\Data\DatosDeEsquema;
use App\Modules\Academico\Enums\CalculoDeComponente;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Enums\RedondeoDeNota;
use App\Modules\Academico\Enums\RegimenDeEsquema;
use App\Modules\Academico\Enums\TipoDeComponente;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Programa;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

final class EsquemaDeEvaluacionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('nombre')->label('Nombre')->required()->maxLength(100)->columnSpanFull(),
                Select::make('idioma_id')->label('Idioma')->options(fn (): array => Idioma::query()->orderBy('nombre')->pluck('nombre', 'id')->all())->placeholder('Todos los idiomas'),
                Select::make('nivel')->label('Nivel')->options(self::opciones(NivelCurricular::cases()))->placeholder('Todos los niveles'),
                Select::make('programa_id')->label('Programa')->options(fn (): array => Programa::query()->orderBy('nombre')->pluck('nombre', 'id')->all())->placeholder('Todos los programas'),
                Select::make('regimen')->label('Régimen')->options(self::opciones(RegimenDeEsquema::cases()))->placeholder('Regular y acelerado')->live()
                    ->helperText('En el acelerado, el grupo dura dos ciclos: marca la columna de la nota de cada uno.'),
                TextInput::make('nota_maxima')->label('Nota máxima')->numeric()->required()->default('20'),
                TextInput::make('nota_minima_aprobatoria')->label('Nota mínima aprobatoria')->numeric()->required()->default('11'),
                DatePicker::make('vigente_desde')->label('Vigente desde')->required(),
                DatePicker::make('vigente_hasta')->label('Vigente hasta')->helperText('Déjalo vacío mientras siga vigente.'),
                Section::make('Columnas')
                    ->description('Las columnas que ingresa el docente y las que se calculan con ellas, en el orden en que se muestran. Una columna calculada solo usa columnas anteriores; una de ellas es la nota del ciclo. Una vez vigente, solo cambian el nombre y la fecha de fin.')
                    ->columnSpanFull()
                    ->schema([
                        self::columnas(),
                    ]),
            ]);
    }

    private static function columnas(): Repeater
    {
        return Repeater::make('componentes')
            ->hiddenLabel()
            ->columns(4)
            ->reorderable()
            ->addActionLabel('Agregar columna')
            ->defaultItems(0)
            ->schema([
                TextInput::make('codigo')->label('Código')->required()->maxLength(30)->live(onBlur: true),
                TextInput::make('nombre')->label('Nombre')->required()->maxLength(80)->live(onBlur: true),
                Select::make('tipo')->label('Tipo')->options(self::opciones(TipoDeComponente::cases()))->default(TipoDeComponente::Ingresada->value)->required()->live(),
                Select::make('ciclo')->label('¿Es la nota del ciclo?')->placeholder('No')
                    ->options(fn (Get $get): array => $get('../../regimen') === RegimenDeEsquema::Acelerado->value
                        ? ['1' => 'Sí, del primer ciclo', '2' => 'Sí, del segundo ciclo']
                        : ['1' => 'Sí']),
                Select::make('calculo')->label('Cálculo')->options(self::opciones(CalculoDeComponente::cases()))->required()->live()
                    ->visible(fn (Get $get): bool => $get('tipo') === TipoDeComponente::Calculada->value),
                Select::make('redondeo')->label('Redondeo')->options(self::opciones(RedondeoDeNota::cases()))->default(RedondeoDeNota::Ninguno->value)->required()
                    ->visible(fn (Get $get): bool => $get('tipo') === TipoDeComponente::Calculada->value),
                Repeater::make('fuentes')
                    ->label('Usa las columnas')
                    ->columnSpan(2)
                    ->columns(2)
                    ->addActionLabel('Agregar columna que usa')
                    ->defaultItems(0)
                    ->visible(fn (Get $get): bool => $get('tipo') === TipoDeComponente::Calculada->value)
                    ->schema([
                        Select::make('codigo')->label('Columna')->required()
                            ->options(fn (Get $get): array => self::columnasDisponibles($get('../../../../componentes'))),
                        TextInput::make('peso')->label('Peso')->integer()->minValue(1)->maxValue(100)->required()
                            ->visible(fn (Get $get): bool => $get('../../calculo') === CalculoDeComponente::Ponderado->value),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function columnasDisponibles(mixed $filas): array
    {
        return collect(is_array($filas) ? $filas : [])
            ->filter(fn (mixed $fila): bool => is_array($fila) && is_string($fila['codigo'] ?? null) && $fila['codigo'] !== '')
            ->mapWithKeys(fn (array $fila): array => [mb_strtolower(trim($fila['codigo'])) => is_string($fila['nombre'] ?? null) && $fila['nombre'] !== '' ? $fila['nombre'] : $fila['codigo']])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function desde(EsquemaEvaluacion $esquema): array
    {
        return [
            ...$esquema->only(['nombre', 'idioma_id', 'programa_id', 'nota_maxima', 'nota_minima_aprobatoria']),
            'nivel' => $esquema->nivel?->value,
            'regimen' => $esquema->regimen?->value,
            'vigente_desde' => $esquema->vigente_desde->toDateString(),
            'vigente_hasta' => $esquema->vigente_hasta?->toDateString(),
            'componentes' => $esquema->componentes()->get()->map(fn (ComponenteEvaluacion $componente): array => [
                'codigo' => $componente->codigo, 'nombre' => $componente->nombre, 'tipo' => $componente->tipo->value, 'calculo' => $componente->calculo?->value,
                'redondeo' => $componente->redondeo->value, 'fuentes' => $componente->fuentes, 'ciclo' => $componente->ciclo === null ? null : (string) $componente->ciclo,
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function datos(array $data): DatosDeEsquema
    {
        $componentes = is_array($data['componentes'] ?? null) ? $data['componentes'] : [];
        $hasta = self::texto($data, 'vigente_hasta');

        return new DatosDeEsquema(
            nombre: self::texto($data, 'nombre'),
            idiomaId: is_numeric($data['idioma_id'] ?? null) ? (int) $data['idioma_id'] : null,
            nivel: NivelCurricular::tryFrom(self::texto($data, 'nivel')),
            notaMaxima: self::texto($data, 'nota_maxima'),
            notaMinimaAprobatoria: self::texto($data, 'nota_minima_aprobatoria'),
            vigenteDesde: CarbonImmutable::parse(self::texto($data, 'vigente_desde')),
            vigenteHasta: $hasta === '' ? null : CarbonImmutable::parse($hasta),
            componentes: array_values(array_map(fn (mixed $fila): array => [
                'codigo' => self::texto($fila, 'codigo'),
                'nombre' => self::texto($fila, 'nombre'),
                'tipo' => self::texto($fila, 'tipo'),
                'calculo' => self::texto($fila, 'calculo'),
                'redondeo' => self::texto($fila, 'redondeo'),
                'fuentes' => array_values(array_map(fn (mixed $fuente): array => ['codigo' => self::texto($fuente, 'codigo'), 'peso' => self::texto($fuente, 'peso')], is_array($fila) && is_array($fila['fuentes'] ?? null) ? $fila['fuentes'] : [])),
                'ciclo' => self::texto($fila, 'ciclo'),
            ], $componentes)),
            programaId: is_numeric($data['programa_id'] ?? null) ? (int) $data['programa_id'] : null,
            regimen: RegimenDeEsquema::tryFrom(self::texto($data, 'regimen')),
        );
    }

    /**
     * @param  list<CalculoDeComponente|NivelCurricular|RedondeoDeNota|RegimenDeEsquema|TipoDeComponente>  $casos
     * @return array<string, string>
     */
    private static function opciones(array $casos): array
    {
        return collect($casos)->mapWithKeys(fn (CalculoDeComponente|NivelCurricular|RedondeoDeNota|RegimenDeEsquema|TipoDeComponente $caso): array => [$caso->value => $caso->etiqueta()])->all();
    }

    private static function texto(mixed $fila, string $clave): string
    {
        $valor = is_array($fila) ? ($fila[$clave] ?? null) : null;

        return is_scalar($valor) ? (string) $valor : '';
    }
}
