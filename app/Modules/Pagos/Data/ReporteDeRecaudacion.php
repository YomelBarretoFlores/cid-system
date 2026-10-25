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

    public function nombreDeArchivo(string $extension): string
    {
        return "recaudacion-{$this->filtros->desde->toDateString()}-al-{$this->filtros->hasta->toDateString()}.{$extension}";
    }

    public function ticketPromedioCentimos(): int
    {
        return $this->operaciones === 0 ? 0 : intdiv($this->totalCentimos * 2 + $this->operaciones, $this->operaciones * 2);
    }

    public static function soles(int $centimos): string
    {
        return number_format(intdiv($centimos, 100), 0, '.', ',').'.'.self::decimales($centimos);
    }

    // Plain decimal without thousands separators, for spreadsheets.
    public static function decimal(int $centimos): string
    {
        return intdiv($centimos, 100).'.'.self::decimales($centimos);
    }

    private static function decimales(int $centimos): string
    {
        return str_pad((string) ($centimos % 100), 2, '0', STR_PAD_LEFT);
    }
}
