<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Listeners;

use App\Modules\Academico\Contracts\CiclosAnteriores;
use App\Modules\Academico\Data\DatosDeCiclo;
use App\Modules\Certificacion\Actions\RegistrarAprobacionesSinActa;
use App\Modules\Certificacion\Data\AprobacionSinActa;
use App\Modules\Certificacion\Data\CicloAprobadoSinActa;
use App\Modules\Certificacion\Enums\FormaDeAprobacion;
use App\Modules\Matricula\Events\ClasificacionRegistrada;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;

// The cycles a placement skips count as passed, without a grade or hours. Rows are never removed, so a correction to a lower cycle keeps them.
#[Backoff([10, 60])]
#[Tries(3)]
final readonly class AlRegistrarClasificacion implements ShouldQueue
{
    public function __construct(private CiclosAnteriores $ciclosAnteriores, private RegistrarAprobacionesSinActa $registrar) {}

    public function handle(ClasificacionRegistrada $evento): void
    {
        $anteriores = ($this->ciclosAnteriores)($evento->cicloId);

        ($this->registrar)(
            new AprobacionSinActa($evento->personaId, $anteriores->idioma, FormaDeAprobacion::Clasificacion, null, CarbonImmutable::parse($evento->fechaExamen), 'solicitud_clasificacion', $evento->solicitudId, $evento->usuarioId),
            array_map(fn (DatosDeCiclo $ciclo): CicloAprobadoSinActa => new CicloAprobadoSinActa($ciclo->nivel, $ciclo->numero, $ciclo->nombre, null), $anteriores->ciclos),
        );
    }
}
