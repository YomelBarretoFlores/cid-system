<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Idioma;

final readonly class ListarCatalogoDeIdiomas implements CatalogoDeIdiomas
{
    public function __invoke(): array
    {
        return array_values(Idioma::query()
            ->with(['ciclos' => fn ($ciclos) => $ciclos->orderBy('numero')])
            ->orderBy('nombre')
            ->get()
            ->map(fn (Idioma $idioma): IdiomaDelCatalogo => new IdiomaDelCatalogo(
                id: $idioma->id,
                nombre: $idioma->nombre,
                activo: $idioma->activo,
                ciclos: $idioma->ciclos->mapWithKeys(fn (Ciclo $ciclo): array => [$ciclo->id => "Ciclo {$ciclo->numero} ({$ciclo->nivel->etiqueta()})"])->all(),
                horasPorCiclo: $idioma->ciclos->mapWithKeys(fn (Ciclo $ciclo): array => [$ciclo->id => $ciclo->horas_lectivas])->all(),
                curriculo: $idioma->curriculo,
            ))
            ->all());
    }
}
