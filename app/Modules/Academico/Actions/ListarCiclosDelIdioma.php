<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\CiclosDelIdioma;
use App\Modules\Academico\Data\CicloDelIdioma;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Idioma;

final readonly class ListarCiclosDelIdioma implements CiclosDelIdioma
{
    public function __invoke(string $idioma): array
    {
        $idiomaId = Idioma::query()->where('nombre', $idioma)->value('id');

        if ($idiomaId === null) {
            return [];
        }

        return array_values(Ciclo::query()->where('idioma_id', $idiomaId)->orderBy('numero')->get()
            ->map(fn (Ciclo $ciclo): CicloDelIdioma => new CicloDelIdioma($ciclo->id, $ciclo->numero, $ciclo->nivel, $ciclo->nombre, $ciclo->horas_lectivas, $ciclo->activo))
            ->all());
    }
}
