<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\Tramites\Pages;

use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Modules\Tramites\Filament\Resources\Tramites\TramiteResource;
use App\Modules\Tramites\Models\Tramite;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

final class ListTramites extends ListRecords
{
    protected static string $resource = TramiteResource::class;

    /**
     * @var array<int, string>|null
     */
    private ?array $etiquetas = null;

    public function etiquetaDePersona(int $personaId): string
    {
        // One lookup for the whole page instead of one per row.
        $this->etiquetas ??= app(EtiquetasDePersonas::class)($this->personasDeLaPagina());

        return $this->etiquetas[$personaId] ?? '—';
    }

    /**
     * @return list<int>
     */
    private function personasDeLaPagina(): array
    {
        $registros = $this->getTableRecords();
        $tramites = $registros instanceof Collection ? $registros : collect($registros->items());

        return array_values(array_unique($tramites->map(fn (Tramite $tramite): int => $tramite->persona_id)->all()));
    }
}
