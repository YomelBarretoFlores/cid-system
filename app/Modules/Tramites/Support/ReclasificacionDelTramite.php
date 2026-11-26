<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Certificacion\Contracts\AprobacionesPorReclasificacion;
use App\Modules\Matricula\Contracts\HabilitacionPorTramite;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Tramites\Data\DatosDeResolucion;
use App\Modules\Tramites\Data\ResultadoDeAccionFinal;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Staff assign the cycle like a placement result: it may be lower than the current one, and the cycles before it count as passed by placement.
final readonly class ReclasificacionDelTramite
{
    public function __construct(
        private CatalogoDeIdiomas $catalogoDeIdiomas,
        private AprobacionesPorReclasificacion $aprobaciones,
        private HabilitacionPorTramite $habilitacion,
    ) {}

    public function resolver(Tramite $tramite, User $usuario, DatosDeResolucion $resolucion): ResultadoDeAccionFinal
    {
        $idioma = (string) ($tramite->datos['idioma'] ?? '');
        $ciclos = self::ciclosDe($this->catalogoDeIdiomas, $idioma);
        $cicloId = $resolucion->cicloId;

        throw_unless($cicloId !== null && isset($ciclos[$cicloId]), ReglaDeNegocioIncumplida::class, "Elige un ciclo de {$idioma}.");

        ($this->habilitacion)($tramite->persona_id, $cicloId, OrigenHabilitacion::Reclasificacion, $usuario->id, false);
        ($this->aprobaciones)($tramite->persona_id, $cicloId, $tramite->id, $usuario->id);

        return new ResultadoDeAccionFinal(
            resultado: ['ciclo_id' => $cicloId, 'ciclo' => $ciclos[$cicloId]],
            comentario: "Ciclo asignado en la reclasificación: {$ciclos[$cicloId]}.",
        );
    }

    /**
     * @return array<int, string> cycle id => label, in study order
     */
    public static function ciclosDe(CatalogoDeIdiomas $catalogo, string $idioma): array
    {
        return collect($catalogo())->first(fn (IdiomaDelCatalogo $candidato): bool => $candidato->nombre === $idioma)->ciclos ?? [];
    }
}
