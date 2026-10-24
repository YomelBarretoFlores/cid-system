<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Pagos\Pages;

use App\Modules\Pagos\Filament\Resources\Pagos\PagoResource;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

final class ListPagos extends ListRecords
{
    protected static string $resource = PagoResource::class;

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
        $pagos = $registros instanceof Collection ? $registros : collect($registros->items());

        return array_values(array_unique($pagos->map(fn (Pago $pago): int => $pago->persona_id)->all()));
    }
}
