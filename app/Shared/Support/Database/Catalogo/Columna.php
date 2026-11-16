<?php

declare(strict_types=1);

namespace App\Shared\Support\Database\Catalogo;

final readonly class Columna
{
    public function __construct(public string $tabla, public string $nombre, public string $tipo, public bool $nulo, public ?string $defecto, public bool $generada, public ?string $descripcion) {}
}
