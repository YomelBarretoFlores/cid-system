<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Matriculas\Schemas;

use App\Modules\Academico\Contracts\CatalogoDeGrupos;
use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\TipoMovimiento;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class MatriculaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Matrícula')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('persona_id')
                            ->label('Estudiante')
                            ->formatStateUsing(fn (int $state): string => app(EtiquetasDePersonas::class)([$state])[$state] ?? '—')
                            ->columnSpan(2),
                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (EstadoMatricula $state): string => $state->etiqueta())
                            ->color(fn (EstadoMatricula $state): string => $state === EstadoMatricula::Activa ? 'success' : 'gray'),
                        TextEntry::make('grupo')->label('Grupo')->state(fn (Matricula $record): string => self::grupo($record)->codigo),
                        TextEntry::make('idioma')->label('Idioma y ciclo')->state(fn (Matricula $record): string => self::idiomaYCiclo(self::grupo($record))),
                        TextEntry::make('periodo')->label('Periodo')->state(fn (Matricula $record): string => self::grupo($record)->periodo),
                        TextEntry::make('horario')->label('Horario')->state(fn (Matricula $record): string => implode(' · ', self::grupo($record)->horarios) ?: 'Por confirmar'),
                        TextEntry::make('condicion')->label('Condición')->formatStateUsing(fn (CondicionMatricula $state): string => $state->etiqueta()),
                        TextEntry::make('matriculado_en')->label('Matriculado el')->dateTime('d/m/Y H:i'),
                        TextEntry::make('retirado_en')->label('Retirado el')->dateTime('d/m/Y H:i')->visible(fn (Matricula $record): bool => $record->retirado_en !== null),
                    ]),
                Section::make('Traslados y retiros')
                    ->schema([
                        RepeatableEntry::make('movimientos')
                            ->hiddenLabel()
                            ->placeholder('Sin traslados ni retiros.')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('tipo')->label('Movimiento')->badge()->formatStateUsing(fn (TipoMovimiento $state): string => $state->etiqueta()),
                                TextEntry::make('grupo_origen_id')->label('Grupo de origen')->formatStateUsing(fn (int $state): string => self::etiquetaDeGrupo($state)),
                                TextEntry::make('grupo_destino_id')->label('Grupo de destino')->formatStateUsing(fn (int $state): string => self::etiquetaDeGrupo($state))->placeholder('—'),
                                TextEntry::make('motivo')->label('Motivo')->columnSpan(2),
                                TextEntry::make('autorizador.name')->label('Autorizado por'),
                                TextEntry::make('efectuado_en')->label('Fecha')->dateTime('d/m/Y H:i'),
                            ]),
                    ]),
            ]);
    }

    private static function grupo(Matricula $matricula): FichaDeGrupo
    {
        return app(DetalleDeGrupos::class)([$matricula->grupo_id])[$matricula->grupo_id];
    }

    private static function idiomaYCiclo(FichaDeGrupo $grupo): string
    {
        return "{$grupo->idioma} · Ciclo {$grupo->cicloNumero} ({$grupo->nivel})";
    }

    private static function etiquetaDeGrupo(int $grupoId): string
    {
        return app(CatalogoDeGrupos::class)()[$grupoId] ?? '—';
    }
}
