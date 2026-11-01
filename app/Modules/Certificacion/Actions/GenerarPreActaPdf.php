<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Support\ActaPrevia;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Ui\Formato;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

/** The grades registered so far in an open group, printed as a working document; a closed acta has its own PDF. */
final readonly class GenerarPreActaPdf
{
    public function __construct(private ActaPrevia $actaPrevia) {}

    public function __invoke(int $grupoId): PdfBuilder
    {
        if (Acta::query()->where('grupo_id', $grupoId)->where('estado', EstadoActa::Cerrada)->exists()) {
            throw new ReglaDeNegocioIncumplida('El acta del grupo ya está cerrada; descarga el acta final.');
        }

        $acta = $this->actaPrevia->armar($grupoId);

        return Pdf::view('certificacion::pdf.pre-acta', [
            'acta' => $acta,
            'generadaEl' => Formato::fechaCorta(now('America/Lima')).' '.now('America/Lima')->format('H:i'),
        ])->format('a4')->landscape()->inline("pre-acta-{$acta->grupoCodigo}.pdf");
    }
}
