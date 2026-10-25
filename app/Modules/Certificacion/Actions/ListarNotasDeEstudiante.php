<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Data\NotaDelHistorial;
use App\Modules\Certificacion\Support\HistorialDeNotas;

// A student's final grades by language, only from closed grade sheets, for the API.
final readonly class ListarNotasDeEstudiante
{
    /**
     * @return array<string, list<NotaDelHistorial>> by language name
     */
    public function __invoke(int $personaId): array
    {
        return array_map(fn (string $idioma): array => HistorialDeNotas::de($personaId, $idioma), HistorialDeNotas::idiomasDe($personaId));
    }
}
