<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Habilitaciones\Pages;

use App\Modules\Matricula\Filament\Resources\Habilitaciones\Actions\AccionesDeHabilitacion;
use App\Modules\Matricula\Filament\Resources\Habilitaciones\HabilitacionResource;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\Concerns\ConsultaElCatalogoDeIdiomas;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

final class ListHabilitaciones extends ListRecords
{
    use ConsultaElCatalogoDeIdiomas;

    #[\Override]
    protected static string $resource = HabilitacionResource::class;

    /**
     * @var array<int, string>|null
     */
    private ?array $personas = null;

    public function getSubheading(): string
    {
        return 'El ciclo en el que cada estudiante puede matricularse por idioma. Sin registro, el estudiante empieza en el primer ciclo.';
    }

    public function etiquetaDePersona(int $personaId): string
    {
        // One lookup for the whole page instead of one per row.
        $registros = $this->getTableRecords();
        $habilitaciones = $registros instanceof Collection ? $registros : collect($registros->items());
        $this->personas ??= app(EtiquetasDePersonas::class)(array_values(array_unique($habilitaciones->map(fn (Habilitacion $habilitacion): int => $habilitacion->persona_id)->all())));

        return $this->personas[$personaId] ?? '—';
    }

    protected function getHeaderActions(): array
    {
        return [AccionesDeHabilitacion::ajustar()];
    }
}
