<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages;

use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\Concerns\ConsultaElCatalogoDeIdiomas;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\SolicitudClasificacionResource;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

final class ListSolicitudesClasificacion extends ListRecords
{
    use ConsultaElCatalogoDeIdiomas;

    #[\Override]
    protected static string $resource = SolicitudClasificacionResource::class;

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

    /**
     * @return list<int>
     */
    private function personasDeLaPagina(): array
    {
        $registros = $this->getTableRecords();
        $solicitudes = $registros instanceof Collection ? $registros : collect($registros->items());

        return array_values(array_unique($solicitudes->map(fn (SolicitudClasificacion $solicitud): int => $solicitud->persona_id)->all()));
    }
}
