<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

final readonly class NotaDelComponente
{
    public function __construct(
        public string $codigo,
        public string $nombre,
        public ?string $nota,
    ) {}
}
