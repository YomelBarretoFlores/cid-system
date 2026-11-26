<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\CiclosAnteriores;
use App\Modules\Academico\Data\CiclosAnterioresAlCiclo;
use App\Modules\Academico\Data\DatosDeCiclo;
use App\Modules\Academico\Models\Ciclo;

final readonly class ListarCiclosAnteriores implements CiclosAnteriores
{
    public function __invoke(int $cicloId): CiclosAnterioresAlCiclo
    {
        $ciclo = Ciclo::query()->with('idioma')->findOrFail($cicloId);

        return new CiclosAnterioresAlCiclo($ciclo->idioma->nombre, array_values(Ciclo::query()
            ->where(['idioma_id' => $ciclo->idioma_id, 'activo' => true])
            ->where('numero', '<', $ciclo->numero)
            ->orderBy('numero')
            ->get()
            ->map(fn (Ciclo $anterior): DatosDeCiclo => new DatosDeCiclo($anterior->numero, $anterior->nivel, $anterior->nombre, $anterior->horas_lectivas, $anterior->activo))
            ->all()));
    }
}
