<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Personas;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Filament\Resources\Matriculas\MatriculaResource;
use App\Modules\Matricula\Models\Matricula;
use App\Shared\Support\Filament\IdiomaConBandera;
use App\Shared\Support\Filament\TablaDeLaPersona;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

final class MatriculasDeLaPersona extends TablaDeLaPersona
{
    /** @var array<int, FichaDeGrupo>|null */
    private ?array $grupos = null;

    protected function permiso(): string
    {
        return PermisoMatricula::Ver->value;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Matricula::query()->where('persona_id', $this->personaId))
            ->defaultSort('matriculado_en', 'desc')
            ->columns([
                TextColumn::make('grupo_id')->label('Grupo')->formatStateUsing(fn (int $state): HtmlString|string => $this->grupo($state)),
                TextColumn::make('ciclo')->label('Ciclo')->state(fn (Matricula $record): string => $this->ciclo($record->grupo_id)),
                TextColumn::make('condicion')->label('Condición')->formatStateUsing(fn (CondicionMatricula $state): string => $state->etiqueta()),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn (EstadoMatricula $state): string => $state->etiqueta())
                    ->color(fn (EstadoMatricula $state): string => $state === EstadoMatricula::Activa ? 'success' : 'gray'),
                TextColumn::make('matriculado_en')->label('Matriculado el')->dateTime('d/m/Y H:i', 'America/Lima'),
            ])
            ->recordUrl(fn (Matricula $record): string => MatriculaResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Sin matrículas')
            ->emptyStateDescription('Aún no se ha matriculado en ningún grupo.')
            ->paginated([10, 25]);
    }

    private function grupo(int $grupoId): HtmlString|string
    {
        $grupo = $this->fichas()[$grupoId] ?? null;

        return $grupo instanceof FichaDeGrupo ? IdiomaConBandera::html($grupo->idioma, "{$grupo->codigo} · {$grupo->periodo}") : '—';
    }

    private function ciclo(int $grupoId): string
    {
        $grupo = $this->fichas()[$grupoId] ?? null;

        return $grupo instanceof FichaDeGrupo ? "{$grupo->nivel} {$grupo->cicloNumero}" : '—';
    }

    /**
     * @return array<int, FichaDeGrupo>
     */
    private function fichas(): array
    {
        // One lookup for the page instead of one per row.
        return $this->grupos ??= app(DetalleDeGrupos::class)(array_values(Matricula::query()->where('persona_id', $this->personaId)->distinct()->pluck('grupo_id')->map(fn (int $id): int => $id)->all()));
    }
}
