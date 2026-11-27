<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

use App\Modules\Tablero\Data\ColumnaDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Data\SeccionDeReporte;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// One sheet per section of the report.
final readonly class LibroDelReporte implements Export, WithMultipleSheets
{
    public function __construct(private ReporteGeneral $reporte) {}

    /**
     * @return list<HojaDelTablero>
     */
    public function sheets(): array
    {
        return array_map(fn (SeccionDeReporte $seccion): HojaDelTablero => new HojaDelTablero(
            Str::limit($seccion->titulo, 31, ''),
            array_map(fn (ColumnaDeReporte $columna): string => $columna->nombre, $seccion->columnas),
            [...$seccion->filas, ...$seccion->totales],
            $this->formatos($seccion),
        ), $this->reporte->secciones);
    }

    /**
     * @return array<string, string>
     */
    private function formatos(SeccionDeReporte $seccion): array
    {
        $formatos = [];

        foreach ($seccion->columnas as $indice => $columna) {
            $formato = $columna->tipo->formatoExcel();

            if ($formato !== null) {
                $formatos[Coordinate::stringFromColumnIndex($indice + 1)] = $formato;
            }
        }

        return $formatos;
    }
}
