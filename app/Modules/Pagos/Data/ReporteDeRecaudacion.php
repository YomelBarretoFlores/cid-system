<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

final readonly class ReporteDeRecaudacion
{
    /**
     * @param  list<FilaDeConcepto>  $porConcepto
     * @param  list<FilaDeIdioma>  $porIdiomaYNivel
     * @param  list<DetalleDeRecaudacion>  $detalle
     */
    public function __construct(
        public FiltrosDeRecaudacion $filtros,
        public int $totalCentimos,
        public int $operaciones,
        public array $porConcepto,
        public array $porIdiomaYNivel,
        public array $detalle,
    ) {}

    public static function soles(int $centimos): string
    {
        return number_format(intdiv($centimos, 100), 0, '.', ',').'.'.str_pad((string) ($centimos % 100), 2, '0', STR_PAD_LEFT);
    }
}
