<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

final readonly class ModalidadDelCatalogo
{
    public function __construct(
        public string $codigo,
        public string $nombre,
        public ?string $descripcion,
        public bool $activo,
    ) {}
}
