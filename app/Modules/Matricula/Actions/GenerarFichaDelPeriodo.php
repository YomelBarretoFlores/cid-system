<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Shared\Ui\CodigoQr;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

// The QR and the printed address open this same record again; the public copy masks the document number (Ley 29733).
final readonly class GenerarFichaDelPeriodo
{
    public function __construct(private ComponerFichaDelPeriodo $componer) {}

    public function __invoke(int $personaId, int $periodoId, bool $publica = false): PdfBuilder
    {
        $ficha = ($this->componer)($personaId, $periodoId);
        abort_if($ficha->matriculas === [], 404);
        $verificacion = route('fichas.verificar', $ficha->codigo);

        return Pdf::view('matricula::pdf.ficha', ['ficha' => $ficha, 'publica' => $publica, 'verificacion' => $verificacion, 'qr' => CodigoQr::comoDataUri($verificacion)])
            ->format('a3')
            ->inline('ficha-de-matricula-'.Str::slug($ficha->periodo).'.pdf');
    }
}
