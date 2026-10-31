<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

final readonly class RecordDelIdioma
{
    /**
     * @param  list<CicloDelRecord>  $ciclos  by cycle number
     * @param  list<FaltantesDelNivel>  $faltantes  one per level the student reached
     */
    public function __construct(
        public string $idioma,
        public array $ciclos,
        public ?string $promedio,
        public array $faltantes,
    ) {}
}
