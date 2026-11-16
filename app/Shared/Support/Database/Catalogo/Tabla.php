<?php

declare(strict_types=1);

namespace App\Shared\Support\Database\Catalogo;

final readonly class Tabla
{
    public function __construct(public string $nombre, public ?string $descripcion) {}
}
