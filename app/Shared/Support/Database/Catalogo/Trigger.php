<?php

declare(strict_types=1);

namespace App\Shared\Support\Database\Catalogo;

final readonly class Trigger
{
    public function __construct(public string $tabla, public string $nombre, public string $momento, public string $eventos, public string $funcion) {}
}
