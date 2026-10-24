<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Grupos\Schemas;

use App\Modules\Academico\Data\DatosDeGrupo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

final class GrupoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('periodo_id')
                    ->label('Periodo')
                    ->relationship('periodo', 'nombre')
                    ->preload()
                    ->required()
                    ->disabledOn('edit'),
                Select::make('ciclo_id')
                    ->label('Idioma y ciclo')
                    ->relationship('ciclo', 'nombre', fn (Builder $query): Builder => $query
                        ->where('activo', true)
                        ->whereHas('idioma', fn (Builder $idioma): Builder => $idioma->where('activo', true))
                        ->with('idioma')
                        ->orderBy('idioma_id')
                        ->orderBy('numero'))
                    ->getOptionLabelFromRecordUsing(fn (Ciclo $record): string => self::etiquetaDeCiclo($record))
                    ->preload()
                    ->required()
                    ->disabledOn('edit')
                    ->helperText('El periodo y el ciclo no cambian después de crear el grupo.'),
                TextInput::make('codigo')->label('Código de grupo')->required()->maxLength(20),
                Select::make('modalidad')
                    ->label('Modalidad')
                    ->options(collect(Modalidad::cases())->mapWithKeys(fn (Modalidad $modalidad): array => [$modalidad->value => $modalidad->etiqueta()])->all())
                    ->required()
                    ->live(),
                Toggle::make('es_acelerado')->label('Acelerado')->default(false),
                Select::make('aula_id')
                    ->label('Aula')
                    ->relationship('aula', 'codigo', fn (Builder $query, ?Grupo $record): Builder => $query
                        ->where(fn (Builder $disponibles): Builder => $disponibles->where('activo', true)->when($record?->aula_id, fn (Builder $q, int $actual): Builder => $q->orWhereKey($actual)))
                        ->orderBy('codigo'))
                    ->getOptionLabelFromRecordUsing(fn (Aula $record): string => "{$record->codigo} · {$record->nombre} ({$record->capacidad} estudiantes)")
                    ->preload()
                    ->required()
                    ->visible(fn (Get $get): bool => ! self::esVirtual($get)),
                TextInput::make('enlace_virtual')
                    ->label('Enlace de la clase virtual')
                    ->url()
                    ->maxLength(255)
                    ->required(fn (Get $get): bool => self::esVirtual($get))
                    ->visible(fn (Get $get): bool => self::modalidad($get) !== Modalidad::Presencial),
                TextInput::make('cupo_maximo')->label('Cupo máximo')->integer()->minValue(1)->maxValue(999)->required(),
                TextInput::make('cupo_minimo')->label('Cupo mínimo de apertura')->integer()->minValue(0)->maxValue(999)->required(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function datos(array $data): DatosDeGrupo
    {
        return new DatosDeGrupo(
            periodoId: self::entero($data['periodo_id'] ?? null),
            cicloId: self::entero($data['ciclo_id'] ?? null),
            codigo: trim(self::texto($data['codigo'] ?? null)),
            modalidad: Modalidad::from(self::texto($data['modalidad'] ?? null)),
            esAcelerado: (bool) ($data['es_acelerado'] ?? false),
            cupoMaximo: self::entero($data['cupo_maximo'] ?? null),
            cupoMinimo: self::entero($data['cupo_minimo'] ?? null),
            aulaId: filled($data['aula_id'] ?? null) ? self::entero($data['aula_id']) : null,
            enlaceVirtual: filled($data['enlace_virtual'] ?? null) ? trim(self::texto($data['enlace_virtual'])) : null,
        );
    }

    public static function etiquetaDeCiclo(Ciclo $ciclo): string
    {
        return "{$ciclo->idioma->nombre} · Ciclo {$ciclo->numero} ({$ciclo->nivel->etiqueta()})";
    }

    private static function modalidad(Get $get): ?Modalidad
    {
        $estado = $get('modalidad');

        return $estado instanceof Modalidad ? $estado : Modalidad::tryFrom(self::texto($estado));
    }

    private static function esVirtual(Get $get): bool
    {
        return self::modalidad($get) === Modalidad::Virtual;
    }

    private static function entero(mixed $valor): int
    {
        return is_numeric($valor) ? (int) $valor : 0;
    }

    private static function texto(mixed $valor): string
    {
        return is_scalar($valor) ? (string) $valor : '';
    }
}
