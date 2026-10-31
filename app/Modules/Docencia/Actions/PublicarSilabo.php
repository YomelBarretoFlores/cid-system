<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Enums\EstadoSilabo;
use App\Modules\Docencia\Models\Silabo;
use App\Modules\Docencia\Support\RedaccionDelSilabo;
use App\Shared\Auth\Models\User;

// Saves and publishes the syllabus once every section, week and evaluation row is complete; publishing again keeps the first date.
final readonly class PublicarSilabo
{
    public function __construct(private RedaccionDelSilabo $redaccion) {}

    /**
     * @param  array<mixed>  $datos  texts, `semanas` by week number and `evaluaciones` by component code
     */
    public function __invoke(int $grupoId, int $docenteId, array $datos, User $usuario): Silabo
    {
        return $this->redaccion->escribir($grupoId, $docenteId, $datos, function (Silabo $silabo) use ($usuario): void {
            if ($silabo->estado === EstadoSilabo::Publicado || ! $silabo->estado->puedeTransitarA(EstadoSilabo::Publicado)) {
                return;
            }

            $silabo->fill(['estado' => EstadoSilabo::Publicado, 'publicado_en' => now(), 'publicado_por' => $usuario->id]);
        });
    }
}
