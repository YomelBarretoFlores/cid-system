<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

final readonly class Desercion
{
    /**
     * @param  list<FilaDeDesercion>  $porNivel
     * @param  list<FilaDeDesercion>  $porIdioma
     */
    public function __construct(
        public FilaDeDesercion $total,
        public array $porNivel,
        public array $porIdioma,
    ) {}
}
