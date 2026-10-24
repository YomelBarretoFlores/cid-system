<?php

declare(strict_types=1);

namespace App\Modules\Personas\Data;

use App\Modules\Personas\Models\Persona;

final readonly class FichaDeEstudiante
{
    public function __construct(
        public int $personaId,
        public string $nombres,
        public string $nombreCompleto,
        public string $documento,
    ) {}

    public static function desde(Persona $persona): self
    {
        return new self(
            personaId: $persona->id,
            nombres: $persona->nombres,
            nombreCompleto: $persona->nombre_completo,
            documento: "{$persona->tipo_documento->etiqueta()} {$persona->numero_documento}",
        );
    }
}
