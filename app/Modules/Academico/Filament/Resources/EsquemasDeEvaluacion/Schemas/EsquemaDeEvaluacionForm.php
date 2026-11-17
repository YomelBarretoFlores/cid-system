<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Schemas;

use App\Modules\Academico\Data\DatosDeEsquema;
use App\Modules\Academico\Enums\GrupoDeComponente;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Idioma;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
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
                Select::make('nivel')->label('Nivel')->options(collect(NivelCurricular::cases())->mapWithKeys(fn (NivelCurricular $nivel): array => [$nivel->value => $nivel->etiqueta()])->all())->placeholder('Todos los niveles'),
                TextInput::make('nota_maxima')->label('Nota máxima')->numeric()->required()->default('20'),
                TextInput::make('nota_minima_aprobatoria')->label('Nota mínima aprobatoria')->numeric()->required()->default('11'),
                DatePicker::make('vigente_desde')->label('Vigente desde')->required(),
                DatePicker::make('vigente_hasta')->label('Vigente hasta')->helperText('Déjalo vacío mientras siga vigente.'),
                Section::make('Componentes')
                    ->description('Se promedian las habilidades y los exámenes por separado; la nota final es el promedio de ambos, redondeado al entero. Una vez vigente, solo cambian el nombre y la fecha de fin.')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('componentes')
                            ->hiddenLabel()
                            ->columns(3)
                            ->reorderable()
                            ->addActionLabel('Agregar componente')
                            ->defaultItems(0)
                            ->schema([
                                TextInput::make('codigo')->label('Código')->required()->maxLength(30),
                                TextInput::make('nombre')->label('Nombre')->required()->maxLength(80),
                                Select::make('grupo')->label('Grupo')->options(collect(GrupoDeComponente::cases())->mapWithKeys(fn (GrupoDeComponente $grupo): array => [$grupo->value => $grupo->etiqueta()])->all())->required(),
                            ]),
                    ]),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function desde(EsquemaEvaluacion $esquema): array
    {
        return [
            ...$esquema->only(['nombre', 'idioma_id', 'nota_maxima', 'nota_minima_aprobatoria']),
            'nivel' => $esquema->nivel?->value,
            'vigente_desde' => $esquema->vigente_desde->toDateString(),
            'vigente_hasta' => $esquema->vigente_hasta?->toDateString(),
            'componentes' => $esquema->componentes()->get()->map(fn ($componente): array => ['codigo' => $componente->codigo, 'nombre' => $componente->nombre, 'grupo' => $componente->grupo->value])->all(),
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
                'grupo' => self::texto($fila, 'grupo'),
            ], $componentes)),
        );
    }

    private static function texto(mixed $fila, string $clave): string
    {
        $valor = is_array($fila) ? ($fila[$clave] ?? null) : null;

        return is_scalar($valor) ? (string) $valor : '';
    }
}
