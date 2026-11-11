<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Matriculas\Pages;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Filament\Resources\Matriculas\Actions\AccionesDeMatricula;
use App\Modules\Matricula\Filament\Resources\Matriculas\MatriculaResource;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\Movimiento;
use Filament\Resources\Pages\ViewRecord;

final class ViewMatricula extends ViewRecord
{
    #[\Override]
    protected static string $resource = MatriculaResource::class;

    /**
     * @var array<int, FichaDeGrupo>|null
     */
    private ?array $grupos = null;

    public function grupo(int $grupoId): ?FichaDeGrupo
    {
        // One lookup for the enrollment's group and every group in its movements.
        $this->grupos ??= app(DetalleDeGrupos::class)($this->gruposInvolucrados());

        return $this->grupos[$grupoId] ?? null;
    }

    protected function getHeaderActions(): array
    {
        return [
            AccionesDeMatricula::descargarFicha(),
            ...AccionesDeMatricula::movimientos(),
        ];
    }

    /**
     * @return list<int>
     */
    private function gruposInvolucrados(): array
    {
        $matricula = $this->getRecord();

        if (! $matricula instanceof Matricula) {
            return [];
        }

        $ids = $matricula->movimientos
            ->flatMap(fn (Movimiento $movimiento): array => [$movimiento->grupo_origen_id, $movimiento->grupo_destino_id])
            ->all();

        return array_values(array_unique(array_map(intval(...), array_filter([$matricula->grupo_id, ...$ids]))));
    }
}
