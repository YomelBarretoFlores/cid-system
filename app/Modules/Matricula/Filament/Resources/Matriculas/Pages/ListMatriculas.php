<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Matriculas\Pages;

use App\Modules\Academico\Contracts\CatalogoDeGrupos;
use App\Modules\Matricula\Filament\Resources\Matriculas\MatriculaResource;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

final class ListMatriculas extends ListRecords
{
    protected static string $resource = MatriculaResource::class;

    /**
     * @var array<int, string>|null
     */
    private ?array $personas = null;

    /**
     * @var array<int, string>|null
     */
    private ?array $grupos = null;

    public function etiquetaDePersona(int $personaId): string
    {
        // One lookup for the whole page instead of one per row.
        $this->personas ??= app(EtiquetasDePersonas::class)($this->personasDeLaPagina());

        return $this->personas[$personaId] ?? '—';
    }

    public function etiquetaDeGrupo(int $grupoId): string
    {
        $this->grupos ??= app(CatalogoDeGrupos::class)();

        return $this->grupos[$grupoId] ?? '—';
    }

    /**
     * @return list<int>
     */
    private function personasDeLaPagina(): array
    {
        $registros = $this->getTableRecords();
        $matriculas = $registros instanceof Collection ? $registros : collect($registros->items());

        return array_values(array_unique($matriculas->map(fn (Matricula $matricula): int => $matricula->persona_id)->all()));
    }
}
