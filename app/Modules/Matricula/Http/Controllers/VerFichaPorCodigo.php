<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Http\Controllers;

use App\Modules\Matricula\Actions\GenerarFichaDelPeriodo;
use App\Modules\Matricula\Models\Ficha;
use Spatie\LaravelPdf\PdfBuilder;

// Whoever holds the record, or scans its QR, sees it again as it is today; search engines do not index it.
final class VerFichaPorCodigo
{
    public function __invoke(string $codigo, GenerarFichaDelPeriodo $generar): PdfBuilder
    {
        $ficha = Ficha::query()->where('codigo', $codigo)->firstOrFail();

        return $generar($ficha->persona_id, $ficha->periodo_id, publica: true)->headers(['X-Robots-Tag' => 'noindex, nofollow']);
    }
}
