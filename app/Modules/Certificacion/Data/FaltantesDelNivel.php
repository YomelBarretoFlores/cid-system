<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

final readonly class FaltantesDelNivel
{
    /**
     * @param  list<int>  $ciclos  cycle numbers not passed yet; empty when the level is complete
     */
    public function __construct(
        public string $nivel,
        public array $ciclos,
    ) {}

    public function descripcion(): string
    {
        if ($this->ciclos === []) {
            return "Nivel {$this->nivel} completo";
        }

        $ciclos = $this->ciclos;
        $ultimo = array_pop($ciclos);

        return $ciclos === []
            ? "Falta el ciclo {$ultimo} del nivel {$this->nivel}"
            : 'Faltan los ciclos '.implode(', ', $ciclos)." y {$ultimo} del nivel {$this->nivel}";
    }
}
