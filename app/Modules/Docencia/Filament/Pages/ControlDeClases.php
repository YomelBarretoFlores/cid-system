<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Pages;

use App\Modules\Academico\Contracts\CatalogoDePeriodos;
use App\Modules\Docencia\Enums\EstadoDeClase;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Support\EstadoDeLaClase;
use App\Modules\Docencia\Support\NombresDelControlDeClases;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

final class ControlDeClases extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Docencia';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Control de clases';

    protected static ?string $title = 'Control de clases';

    protected static ?string $slug = 'control-de-clases';

    protected ?string $subheading = 'Clases registradas por los docentes, con los dos llamados y la confirmación del delegado del curso.';

    protected string $view = 'docencia::filament.control-de-clases';

    /**
     * @var array{grupos: array<int, string>, docentes: array<int, string>, delegados: array<int, string>}|null
     */
    private ?array $nombres = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoDocencia::Ver->value) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ClaseDictada::query()->withCount([
                'asistencias',
                'asistencias as presentes_count' => fn (Builder $consulta): Builder => $consulta->where('estado', '<>', 'falta')->whereRaw("coalesce(estado_cierre, 'presente') <> 'falta'"),
            ]))
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('fecha')->orderByDesc('hora_inicio')->orderByDesc('id'))
            ->columns([
                TextColumn::make('fecha')->label('Fecha')->formatStateUsing(fn (ClaseDictada $record): string => $record->fecha->format('d/m/Y')),
                TextColumn::make('horario')->label('Horario')->state(fn (ClaseDictada $record): string => substr($record->hora_inicio, 0, 5).' – '.substr($record->hora_fin, 0, 5)),
                TextColumn::make('grupo_id')->label('Grupo')->formatStateUsing(fn (int $state): string => $this->nombres()['grupos'][$state] ?? '—'),
                TextColumn::make('docente_id')->label('Docente')->formatStateUsing(fn (int $state): string => $this->nombres()['docentes'][$state] ?? '—'),
                TextColumn::make('tema')->label('Tema dictado')->placeholder('—')->wrap()->limit(120),
                TextColumn::make('presentes')->label('Presentes al cierre')->state(fn (ClaseDictada $record): string => "{$record->getAttribute('presentes_count')} / {$record->getAttribute('asistencias_count')}"),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (ClaseDictada $record): string => EstadoDeLaClase::de($record)->etiqueta())
                    ->color(fn (ClaseDictada $record): string => $this->color(EstadoDeLaClase::de($record))),
                TextColumn::make('confirmada_por_matricula_id')
                    ->label('Confirmada por')
                    ->placeholder('—')
                    ->formatStateUsing(fn (int $state): string => $this->nombres()['delegados'][$state] ?? '—'),
                TextColumn::make('observacion')->label('Observación')->placeholder('—')->wrap(),
            ])
            ->filters([
                SelectFilter::make('periodo')
                    ->label('Periodo')
                    ->options(fn (): array => app(CatalogoDePeriodos::class)())
                    ->query(fn (Builder $query, array $data): Builder => is_numeric($data['value'] ?? null)
                        ? $query->whereIn('grupo_id', app(NombresDelControlDeClases::class)->gruposDelPeriodo((int) $data['value']))
                        : $query),
                SelectFilter::make('docente')->label('Docente')->attribute('docente_id')->searchable()->options(fn (): array => app(NombresDelControlDeClases::class)->docentes()),
                SelectFilter::make('grupo')->label('Grupo')->attribute('grupo_id')->searchable()->options(fn (): array => app(NombresDelControlDeClases::class)->grupos()),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options($this->estados())
                    ->query(fn (Builder $query, array $data): Builder => ($estado = EstadoDeClase::tryFrom(is_string($data['value'] ?? null) ? $data['value'] : '')) instanceof EstadoDeClase
                        ? EstadoDeLaClase::filtrar($query, $estado)
                        : $query),
            ])
            ->emptyStateHeading('No hay clases con estos filtros.');
    }

    /**
     * @return array<string, string>
     */
    private function estados(): array
    {
        $orden = [EstadoDeClase::Confirmada, EstadoDeClase::Observada, EstadoDeClase::SinConfirmar, EstadoDeClase::PorConfirmar, EstadoDeClase::RegistroPosterior, EstadoDeClase::RegistroAnterior, EstadoDeClase::LlamadoDeInicio, EstadoDeClase::SinLlamadoDeCierre];

        return array_column(array_map(fn (EstadoDeClase $estado): array => [$estado->value, ucfirst($estado->etiqueta())], $orden), 1, 0);
    }

    private function color(EstadoDeClase $estado): string
    {
        return match ($estado) {
            EstadoDeClase::Confirmada => 'success',
            EstadoDeClase::Observada, EstadoDeClase::SinConfirmar, EstadoDeClase::SinLlamadoDeCierre => 'danger',
            EstadoDeClase::PorConfirmar, EstadoDeClase::LlamadoDeInicio => 'warning',
            EstadoDeClase::RegistroPosterior, EstadoDeClase::RegistroAnterior => 'gray',
        };
    }

    /**
     * One lookup per module for the whole page instead of one per row.
     *
     * @return array{grupos: array<int, string>, docentes: array<int, string>, delegados: array<int, string>}
     */
    private function nombres(): array
    {
        if ($this->nombres !== null) {
            return $this->nombres;
        }

        $registros = $this->getTableRecords();
        /** @var Collection<int, ClaseDictada> $clases */
        $clases = $registros instanceof Collection ? $registros : collect($registros->items());
        $nombres = app(NombresDelControlDeClases::class);

        return $this->nombres = [
            'grupos' => $nombres->codigos(array_values(array_unique($clases->pluck('grupo_id')->map(intval(...))->all()))),
            'docentes' => $nombres->docentes(array_values(array_unique($clases->pluck('docente_id')->map(intval(...))->all()))),
            'delegados' => $nombres->delegados($clases),
        ];
    }
}
