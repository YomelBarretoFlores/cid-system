<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final readonly class Tablero
{
    /**
     * @param  list<FranjaHoraria>  $demandaHoraria
     */
    public function __construct(
        public FiltrosDelTablero $filtros,
        public string $periodo,
        public string $idioma,
        public string $nivel,
        public Recaudacion $recaudacion,
        public PagosPorValidar $pagosPorValidar,
        public RentabilidadPorGrupo $rentabilidad,
        public Matriculas $matriculas,
        public Desercion $desercion,
        public array $demandaHoraria,
    ) {}

    public function nombreDeArchivo(string $extension): string
    {
        return 'tablero-'.Str::slug($this->periodo).'-'.CarbonImmutable::now('America/Lima')->format('Y-m-d').".{$extension}";
    }

    public function alcance(): string
    {
        return "{$this->periodo} · {$this->idioma} · {$this->nivel}";
    }
}
