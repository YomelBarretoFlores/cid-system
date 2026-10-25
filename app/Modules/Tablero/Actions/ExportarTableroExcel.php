<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Tablero\Data\Tablero;
use App\Modules\Tablero\Support\LibroDelTablero;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class ExportarTableroExcel
{
    public function __invoke(Tablero $tablero): BinaryFileResponse
    {
        return Excel::download(new LibroDelTablero($tablero), $tablero->nombreDeArchivo('xlsx'));
    }
}
