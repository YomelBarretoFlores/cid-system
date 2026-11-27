<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\CuentasDocentes\Pages;

use App\Modules\Docencia\Filament\Resources\CuentasDocentes\CuentaDocenteResource;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Contracts\CuentasDePersonas;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Modules\Personas\Data\CuentaDelPortal;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

final class ListCuentasDocentes extends ListRecords
{
    #[\Override]
    protected static string $resource = CuentaDocenteResource::class;

    /**
     * @var array<int, string>|null
     */
    private ?array $etiquetas = null;

    /**
     * @var array<int, CuentaDelPortal>|null
     */
    private ?array $cuentas = null;

    public function etiquetaDePersona(int $personaId): string
    {
        // One lookup for the whole page instead of one per row.
        $this->etiquetas ??= app(EtiquetasDePersonas::class)($this->personasDeLaPagina());

        return $this->etiquetas[$personaId] ?? '—';
    }

    public function cuentaDe(Docente $docente): ?CuentaDelPortal
    {
        $this->cuentas ??= app(CuentasDePersonas::class)($this->personasDeLaPagina());

        return $this->cuentas[$docente->persona_id] ?? null;
    }

    // After a change the next render must read the accounts again.
    public function olvidarCuentas(): void
    {
        $this->cuentas = null;
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
