<?php

declare(strict_types=1);

namespace App\Shared\Support\Database\Catalogo;

final readonly class Restriccion
{
    public function __construct(public string $tabla, public string $nombre, public string $tipo, public string $definicion, public ?string $referencia) {}
}
