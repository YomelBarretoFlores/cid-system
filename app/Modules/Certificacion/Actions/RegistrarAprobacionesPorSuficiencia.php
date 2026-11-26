<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Contracts\CiclosDelIdioma;
use App\Modules\Academico\Data\CicloDelIdioma;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Contracts\AprobacionesPorSuficiencia;
use App\Modules\Certificacion\Data\AprobacionSinActa;
use App\Modules\Certificacion\Data\CicloAprobadoSinActa;
use App\Modules\Certificacion\Enums\FormaDeAprobacion;
use App\Modules\Certificacion\Support\CiclosAprobadosConNota;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

// The cycles were not attended, so like a placement they carry no hours; unlike it, they carry the exam grade.
final readonly class RegistrarAprobacionesPorSuficiencia implements AprobacionesPorSuficiencia
{
    public function __construct(private CiclosDelIdioma $ciclosDelIdioma, private RegistrarAprobacionesSinActa $registrar) {}

    public function __invoke(int $personaId, string $idioma, NivelCurricular $nivel, string $nota, int $tramiteId, int $usuarioId): int
    {
        return Transaccion::ejecutar(function () use ($personaId, $idioma, $nivel, $nota, $tramiteId, $usuarioId): int {
            $delNivel = array_values(array_filter(($this->ciclosDelIdioma)($idioma), fn (CicloDelIdioma $ciclo): bool => $ciclo->activo && $ciclo->nivel === $nivel));
            throw_if($delNivel === [], ReglaDeNegocioIncumplida::class, "{$idioma} no tiene ciclos activos en el nivel {$nivel->etiqueta()}.");

            $conNota = CiclosAprobadosConNota::de($personaId, $idioma);
            $porRegistrar = array_values(array_filter($delNivel, fn (CicloDelIdioma $ciclo): bool => ! in_array($ciclo->numero, $conNota, true)));

            return ($this->registrar)(
                new AprobacionSinActa($personaId, $idioma, FormaDeAprobacion::Suficiencia, $nota, CarbonImmutable::now('America/Lima'), 'tramite', $tramiteId, $usuarioId),
                array_map(fn (CicloDelIdioma $ciclo): CicloAprobadoSinActa => new CicloAprobadoSinActa($ciclo->nivel, $ciclo->numero, $ciclo->nombre, null), $porRegistrar),
            );
        });
    }
}
