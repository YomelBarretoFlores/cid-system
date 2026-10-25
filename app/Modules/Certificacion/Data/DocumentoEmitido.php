<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

final readonly class DocumentoEmitido
{
    public function __construct(
        public int $id,
        public string $codigo,
    ) {}
}
