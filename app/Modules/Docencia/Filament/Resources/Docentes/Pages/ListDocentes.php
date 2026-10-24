<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes\Pages;

use App\Modules\Docencia\Filament\Resources\Docentes\DocenteResource;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

final class ListDocentes extends ListRecords
{
    protected static string $resource = DocenteResource::class;

    /**
     * @var array<int, string>|null
     */
    private ?array $personas = null;

    public function etiquetaDePersona(int $personaId): string
    {
        // One lookup for the whole page instead of one per row.
        $this->personas ??= app(EtiquetasDePersonas::class)($this->personasDeLaPagina());

        return $this->personas[$personaId] ?? '—';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return list<int>
     */
    private function personasDeLaPagina(): array
    {
        $registros = $this->getTableRecords();
        $docentes = $registros instanceof Collection ? $registros : collect($registros->items());

        return array_values(array_unique($docentes->map(fn (Docente $docente): int => $docente->persona_id)->all()));
    }
}
