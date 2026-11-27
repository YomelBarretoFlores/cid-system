<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Matriculas\Pages;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Filament\Resources\Matriculas\Actions\AccionesDeMatricula;
use App\Modules\Matricula\Filament\Resources\Matriculas\MatriculaResource;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Shared\Support\Filament\IdiomaConBandera;
use Closure;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

final class ListMatriculas extends ListRecords
{
    #[\Override]
    protected static string $resource = MatriculaResource::class;

    /**
     * @var array<int, string>|null
     */
    private ?array $personas = null;

    /**
     * @var array<int, FichaDeGrupo>|null
     */
    private ?array $grupos = null;

    protected function getHeaderActions(): array
    {
        return [AccionesDeMatricula::matricularEnVentanilla()];
    }

    public function etiquetaDePersona(int $personaId): string
    {
        // One lookup for the whole page instead of one per row.
        $this->personas ??= app(EtiquetasDePersonas::class)($this->deLaPagina(fn (Matricula $matricula): int => $matricula->persona_id));

        return $this->personas[$personaId] ?? '—';
    }

    public function grupoConBandera(int $grupoId): HtmlString|string
    {
        $this->grupos ??= app(DetalleDeGrupos::class)($this->deLaPagina(fn (Matricula $matricula): int => $matricula->grupo_id));
        $grupo = $this->grupos[$grupoId] ?? null;

        return $grupo instanceof FichaDeGrupo ? IdiomaConBandera::html($grupo->idioma, "{$grupo->codigo} · {$grupo->periodo}") : '—';
    }

    /**
     * @param  Closure(Matricula): int  $id
     * @return list<int>
     */
    private function deLaPagina(Closure $id): array
    {
        $registros = $this->getTableRecords();
        $matriculas = $registros instanceof Collection ? $registros : collect($registros->items());

        return array_values(array_unique($matriculas->map($id)->all()));
    }
}
