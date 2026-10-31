<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

final readonly class RequisitoDeMatricula
{
    /**
     * @param  list<string>  $detalle
     */
    public function __construct(
        public string $titulo,
        public bool $cumplido,
        public array $detalle,
        public ?string $enlace = null,
        public ?string $textoDelEnlace = null,
    ) {}
}
