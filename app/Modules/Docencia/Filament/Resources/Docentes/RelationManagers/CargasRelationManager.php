<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes\RelationManagers;

use App\Modules\Academico\Contracts\CatalogoDeGrupos;
use App\Modules\Docencia\Actions\AsignarDocente;
use App\Modules\Docencia\Actions\QuitarDocenteDelGrupo;
use App\Modules\Docencia\Actions\ResumirCargaDelDocente;
use App\Modules\Docencia\Data\CargaHoraria;
use App\Modules\Docencia\Data\ResumenDeCarga;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class CargasRelationManager extends RelationManager
{
    protected static string $relationship = 'cargas';

    protected static ?string $title = 'Carga académica';

    private ?ResumenDeCarga $resumen = null;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('grupo_id')
            ->description(fn (): string => $this->totales())
            ->paginated(false)
            ->columns([
                TextColumn::make('grupo')->label('Grupo')->state(fn (CargaAcademica $record): string => $this->fila($record)['grupo']),
                TextColumn::make('idioma')->label('Idioma y nivel')->state(fn (CargaAcademica $record): string => $this->fila($record)['idioma']),
                TextColumn::make('periodo')->label('Periodo')->state(fn (CargaAcademica $record): string => $this->fila($record)['periodo']),
                TextColumn::make('fechas')->label('Inicio y fin')->state(fn (CargaAcademica $record): string => $this->fila($record)['fechas']),
                TextColumn::make('semanas')->label('Semanas')->state(fn (CargaAcademica $record): string => $this->fila($record)['semanas']),
                TextColumn::make('dias')->label('Días de dictado')->state(fn (CargaAcademica $record): string => $this->fila($record)['dias']),
                TextColumn::make('horas')->label('Horas totales')->state(fn (CargaAcademica $record): string => $this->fila($record)['horas']),
            ])
            ->headerActions([
                Action::make('asignarGrupo')
                    ->label('Asignar grupo')
                    ->icon(Heroicon::OutlinedPlus)
                    ->authorize('create')
                    ->schema([
                        Select::make('grupo_id')->label('Grupo')->options(fn (): array => app(CatalogoDeGrupos::class)())->searchable()->required(),
                    ])
                    ->action(fn (Action $action, array $data) => AccionDeNegocio::ejecutar(
                        $action,
                        fn (User $usuario): CargaAcademica => app(AsignarDocente::class)(
                            is_numeric($data['grupo_id'] ?? null) ? (int) $data['grupo_id'] : 0,
                            (int) $this->getOwnerRecord()->getKey(),
                            $usuario,
                        ),
                        'Grupo asignado al docente',
                    )),
            ])
            ->recordActions([
                Action::make('quitarGrupo')
                    ->label('Quitar')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->authorize('delete')
                    ->requiresConfirmation()
                    ->modalDescription('El grupo quedará sin docente asignado.')
                    ->action(fn (Action $action, CargaAcademica $record) => AccionDeNegocio::ejecutar(
                        $action,
                        fn () => app(QuitarDocenteDelGrupo::class)($record->grupo_id, $record->docente_id),
                        'Grupo quitado de la carga del docente',
                    )),
            ]);
    }

    /**
     * @return array{grupo: string, idioma: string, periodo: string, fechas: string, semanas: string, dias: string, horas: string}
     */
    private function fila(CargaAcademica $registro): array
    {
        $carga = collect($this->resumen()->cargas)->first(fn (CargaHoraria $fila): bool => $fila->grupoId === $registro->grupo_id);

        if (! $carga instanceof CargaHoraria) {
            return ['grupo' => '—', 'idioma' => '—', 'periodo' => '—', 'fechas' => '—', 'semanas' => '—', 'dias' => '—', 'horas' => '—'];
        }

        return [
            'grupo' => $carga->codigo,
            'idioma' => "{$carga->idioma} · {$carga->nivel}",
            'periodo' => $carga->periodo,
            'fechas' => "{$carga->inicio->format('d/m/Y')} – {$carga->fin->format('d/m/Y')}",
            'semanas' => (string) $carga->semanas,
            'dias' => $carga->dias !== '' ? $carga->dias : 'Sin horario',
            'horas' => $carga->cancelado ? 'Grupo cancelado' : "{$carga->horas()} h",
        ];
    }

    private function totales(): string
    {
        $totales = $this->resumen()->totalesPorPeriodo;

        if ($totales === []) {
            return 'Sin grupos asignados.';
        }

        return 'Total de horas asignadas: '.implode(' · ', array_map(fn (string $periodo, string $horas): string => "{$periodo}: {$horas} h", array_keys($totales), $totales)).'.';
    }

    private function resumen(): ResumenDeCarga
    {
        // One schedule lookup per request for every row and the period totals.
        return $this->resumen ??= app(ResumirCargaDelDocente::class)((int) $this->getOwnerRecord()->getKey());
    }
}
