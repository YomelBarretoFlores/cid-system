<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

use Illuminate\Support\Str;

final readonly class ReporteGeneral
{
    /**
     * @param  array<string, string>  $alcance  filter label => chosen value, printed under the title
     * @param  list<SeccionDeReporte>  $secciones
     * @param  bool  $formal  printed on the institutional register sheet of the previous system instead of the SGA report sheet
     */
    public function __construct(
        public string $titulo,
        public string $periodo,
        public array $alcance,
        public array $secciones,
        public bool $formal = false,
    ) {}

    public function nombreDeArchivo(string $extension): string
    {
        return Str::slug($this->titulo).'-'.Str::slug($this->periodo).'.'.$extension;
    }
}
