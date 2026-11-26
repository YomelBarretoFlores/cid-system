<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Contracts\NivelesConNotas;
use App\Modules\Certificacion\Data\NivelPorCertificar;
use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Support\HistorialDeNotas;

final readonly class ListarNivelesConNotas implements NivelesConNotas
{
    public function __invoke(int $personaId): array
    {
        $niveles = [];

        foreach (HistorialDeNotas::idiomasDe($personaId) as $idioma) {
            $aprobados = array_map(
                fn (NotaDelHistorial $nota): NivelCurricular => $nota->nivel,
                array_filter(HistorialDeNotas::de($personaId, $idioma), fn (NotaDelHistorial $nota): bool => $nota->estadoFinal === EstadoFinal::Aprobado),
            );

            foreach (NivelCurricular::cases() as $nivel) {
                if (in_array($nivel, $aprobados, true)) {
                    $niveles[] = new NivelPorCertificar($idioma, $nivel);
                }
            }
        }

        return $niveles;
    }
}
