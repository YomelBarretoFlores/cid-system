<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Contracts\CiclosAnteriores;
use App\Modules\Academico\Data\DatosDeCiclo;
use App\Modules\Certificacion\Contracts\AprobacionesPorReclasificacion;
use App\Modules\Certificacion\Data\AprobacionSinActa;
use App\Modules\Certificacion\Data\CicloAprobadoSinActa;
use App\Modules\Certificacion\Enums\FormaDeAprobacion;
use App\Modules\Certificacion\Support\CiclosAprobadosConNota;
use Carbon\CarbonImmutable;

// Same rows as a placement exam, with the trámite as their origin; a lower reclassification keeps the cycles already recorded.
final readonly class RegistrarAprobacionesPorReclasificacion implements AprobacionesPorReclasificacion
{
    public function __construct(private CiclosAnteriores $ciclosAnteriores, private RegistrarAprobacionesSinActa $registrar) {}

    public function __invoke(int $personaId, int $cicloId, int $tramiteId, int $usuarioId): int
    {
        $anteriores = ($this->ciclosAnteriores)($cicloId);
        $conNota = CiclosAprobadosConNota::de($personaId, $anteriores->idioma);
        $porRegistrar = array_values(array_filter($anteriores->ciclos, fn (DatosDeCiclo $ciclo): bool => ! in_array($ciclo->numero, $conNota, true)));

        return ($this->registrar)(
            new AprobacionSinActa($personaId, $anteriores->idioma, FormaDeAprobacion::Clasificacion, null, CarbonImmutable::now('America/Lima'), 'tramite', $tramiteId, $usuarioId),
            array_map(fn (DatosDeCiclo $ciclo): CicloAprobadoSinActa => new CicloAprobadoSinActa($ciclo->nivel, $ciclo->numero, $ciclo->nombre, null), $porRegistrar),
        );
    }
}
