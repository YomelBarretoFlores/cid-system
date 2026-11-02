<?php

declare(strict_types=1);

namespace App\Shared\Auth\Data;

final readonly class DatosDeRol
{
    /**
     * @param  list<string>  $permisos
     */
    public function __construct(
        public string $nombre,
        public array $permisos,
    ) {}
}
