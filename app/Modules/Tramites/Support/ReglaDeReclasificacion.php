<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Matricula\Contracts\FinDeLaUltimaMatricula;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Models\TipoTramite;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

// Reclassification is for CILN students who stopped studying the language for the months the type asks (2 by default).
final readonly class ReglaDeReclasificacion
{
    public const int MESES_POR_DEFECTO = 2;

    public function __construct(private FinDeLaUltimaMatricula $finDeLaUltimaMatricula) {}

    /**
     * @param  array<string, int|string>  $guardados
     */
    public function asegurar(TipoTramite $tipo, int $personaId, array $guardados): void
    {
        if ($tipo->accion_final !== AccionFinal::Reclasificacion) {
            return;
        }

        $idioma = (string) ($guardados['idioma'] ?? '');
        $fin = ($this->finDeLaUltimaMatricula)($personaId, $idioma)
            ?? throw new ReglaDeNegocioIncumplida("La reclasificación es para estudiantes del CILN que dejaron de estudiar: no tienes matrículas en {$idioma}.");
        $meses = $tipo->meses_sin_estudiar ?? self::MESES_POR_DEFECTO;
        $desde = $fin->addMonthsNoOverflow($meses);

        throw_if(
            CarbonImmutable::today('America/Lima')->lt($desde),
            ReglaDeNegocioIncumplida::class,
            "La reclasificación de {$idioma} es para quienes dejaron de estudiar {$meses} meses o más: podrás pedirla desde el {$desde->format('d/m/Y')}.",
        );
    }
}
