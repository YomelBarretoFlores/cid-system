<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\CodigoQr;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Ui\Formato;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class GenerarDocumentoPdf
{
    private const array VISTAS = [
        'constancia_matricula' => 'certificacion::pdf.constancia-de-matricula',
        'constancia_notas' => 'certificacion::pdf.constancia-de-notas',
        'certificado_nivel' => 'certificacion::pdf.certificado-de-nivel',
    ];

    public function __invoke(int $documentoId): PdfBuilder
    {
        $documento = Documento::query()->findOrFail($documentoId);

        if (! $documento->estaVigente()) {
            throw new ReglaDeNegocioIncumplida("El documento {$documento->codigo} está anulado; ya no se emite su PDF.");
        }

        $verificacion = route('verificacion.documento', $documento->codigo);

        return Pdf::view(self::VISTAS[$documento->tipo->value], [
            'documento' => $documento,
            'verificacion' => $verificacion,
            'qr' => CodigoQr::comoDataUri($verificacion),
            'emitidoEl' => Formato::fechaCorta($documento->emitido_en->setTimezone('America/Lima')),
        ])->format('a4')->inline($this->archivo($documento));
    }

    private function archivo(Documento $documento): string
    {
        $prefijo = match ($documento->tipo) {
            TipoDocumento::ConstanciaMatricula => 'constancia-de-matricula',
            TipoDocumento::ConstanciaNotas => 'constancia-de-notas',
            TipoDocumento::CertificadoNivel => 'certificado-de-nivel',
        };

        return "{$prefijo}-{$documento->codigo}.pdf";
    }
}
