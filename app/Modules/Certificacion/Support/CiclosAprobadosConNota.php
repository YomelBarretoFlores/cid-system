<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Enums\FormaDeAprobacion;

// Cycles a placement or exam must not record again: an acta (or a proficiency exam) already graded them.
final class CiclosAprobadosConNota
{
    /**
     * @return list<int> cycle numbers
     */
    public static function de(int $personaId, string $idioma): array
    {
        return array_values(array_map(
            fn (NotaDelHistorial $nota): int => $nota->cicloNumero,
            array_filter(HistorialDeNotas::de($personaId, $idioma), fn (NotaDelHistorial $nota): bool => $nota->estadoFinal === EstadoFinal::Aprobado && $nota->forma !== FormaDeAprobacion::Clasificacion),
        ));
    }
}
