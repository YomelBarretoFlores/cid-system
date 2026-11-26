<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Listeners;

use App\Modules\Academico\Contracts\CiclosAnteriores;
use App\Modules\Academico\Data\DatosDeCiclo;
use App\Modules\Certificacion\Actions\AnularAprobacionesDeClasificacion;
use App\Modules\Certificacion\Actions\RegistrarAprobacionesSinActa;
use App\Modules\Certificacion\Data\AprobacionSinActa;
use App\Modules\Certificacion\Data\CicloAprobadoSinActa;
use App\Modules\Certificacion\Enums\FormaDeAprobacion;
use App\Modules\Certificacion\Support\CiclosAprobadosConNota;
use App\Modules\Matricula\Events\ClasificacionRegistrada;
use App\Shared\Support\Database\Transaccion;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;

// The cycles a placement skips count as passed, without a grade or hours, unless an acta already graded them; a correction to a lower cycle voids the rest.
#[Backoff([10, 60])]
#[Tries(3)]
final readonly class AlRegistrarClasificacion implements ShouldQueue
{
    public function __construct(
        private CiclosAnteriores $ciclosAnteriores,
        private RegistrarAprobacionesSinActa $registrar,
        private AnularAprobacionesDeClasificacion $anular,
    ) {}

    public function handle(ClasificacionRegistrada $evento): void
    {
        $anteriores = ($this->ciclosAnteriores)($evento->cicloId);
        $aprobadosConNota = CiclosAprobadosConNota::de($evento->personaId, $anteriores->idioma);
        $porRegistrar = array_values(array_filter($anteriores->ciclos, fn (DatosDeCiclo $ciclo): bool => ! in_array($ciclo->numero, $aprobadosConNota, true)));

        Transaccion::ejecutar(function () use ($evento, $anteriores, $porRegistrar): void {
            ($this->anular)($evento->solicitudId, array_map(fn (DatosDeCiclo $ciclo): int => $ciclo->numero, $anteriores->ciclos), $evento->usuarioId);
            ($this->registrar)(
                new AprobacionSinActa($evento->personaId, $anteriores->idioma, FormaDeAprobacion::Clasificacion, null, CarbonImmutable::parse($evento->fechaExamen), 'solicitud_clasificacion', $evento->solicitudId, $evento->usuarioId),
                array_map(fn (DatosDeCiclo $ciclo): CicloAprobadoSinActa => new CicloAprobadoSinActa($ciclo->nivel, $ciclo->numero, $ciclo->nombre, null), $porRegistrar),
            );
        });
    }
}
