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
        public ?string $correo = null,
        public ?string $celular = null,
        public ?string $fechaNacimiento = null,
        public ?string $vinculo = null,
        public ?string $direccion = null,
        // Changes with every new photo so the browser does not show a cached one; never the file path itself.
        public ?string $versionDeFoto = null,
        public bool $habilitada = false,
    ) {}

    public static function desde(Persona $persona): self
    {
        return new self(
            personaId: $persona->id,
            nombres: $persona->nombres,
            nombreCompleto: $persona->nombre_completo,
            documento: "{$persona->tipo_documento->etiqueta()} {$persona->numero_documento}",
            correo: $persona->correo,
            celular: $persona->celular,
            fechaNacimiento: $persona->fecha_nacimiento?->format('d/m/Y'),
            vinculo: $persona->vinculo->nombre,
            direccion: $persona->direccion,
            versionDeFoto: $persona->foto === null ? null : hash('crc32b', $persona->foto),
            habilitada: $persona->habilitada_en !== null,
        );
    }
}
