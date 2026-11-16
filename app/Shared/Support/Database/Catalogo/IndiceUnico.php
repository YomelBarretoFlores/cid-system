<?php

declare(strict_types=1);

namespace App\Shared\Support\Database\Catalogo;

final readonly class IndiceUnico
{
    public function __construct(public string $tabla, public string $nombre, public string $definicion) {}
}
