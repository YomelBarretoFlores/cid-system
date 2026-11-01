<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Support\LibroDelReporte;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class ExportarReporteGeneralExcel
{
    public function __invoke(ReporteGeneral $reporte): BinaryFileResponse
    {
        return Excel::download(new LibroDelReporte($reporte), $reporte->nombreDeArchivo('xlsx'));
    }
}
