<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Grupos\Pages;

use App\Modules\Academico\Filament\Resources\Grupos\GrupoResource;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

final class ListGrupos extends ListRecords
{
    #[\Override]
    protected static string $resource = GrupoResource::class;

    /**
     * @var array<int, string>|null
     */
    private ?array $docentes = null;

    public function docenteDelGrupo(int $grupoId): string
    {
        // One lookup for the whole page instead of one per row.
        $this->docentes ??= app(DocentesDeGrupos::class)($this->gruposDeLaPagina());

        return $this->docentes[$grupoId] ?? 'Por asignar';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo grupo'),
        ];
    }

    /**
     * @return list<int>
     */
    private function gruposDeLaPagina(): array
    {
        $registros = $this->getTableRecords();
        $grupos = $registros instanceof Collection ? $registros : collect($registros->items());

        return array_values($grupos->map(fn (Grupo $grupo): int => $grupo->id)->all());
    }
}
