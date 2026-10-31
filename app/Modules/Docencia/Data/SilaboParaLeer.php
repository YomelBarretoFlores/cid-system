<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

final readonly class SilaboParaLeer
{
    /**
     * @param  array<string, string>  $identificacion  label => value, in the order of section I
     */
    public function __construct(
        public int $grupoId,
        public string $grupo,
        public string $curso,
        public array $identificacion,
        public DatosDelSilabo $contenido,
    ) {}

    public function estaPublicado(): bool
    {
        return $this->contenido->estaPublicado();
    }
}
