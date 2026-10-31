<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Http\Controllers;

use App\Modules\Docencia\Actions\GenerarSilaboPdf;
use App\Modules\Docencia\Contracts\LecturaDelSilabo;
use Spatie\LaravelPdf\PdfBuilder;

// Coordination reads drafts too; the PDF marks them as BORRADOR.
final readonly class DescargarSilaboParaPersonal
{
    public function __invoke(int $grupo, LecturaDelSilabo $lectura, GenerarSilaboPdf $generar): PdfBuilder
    {
        return $generar($lectura($grupo) ?? abort(404));
    }
}
