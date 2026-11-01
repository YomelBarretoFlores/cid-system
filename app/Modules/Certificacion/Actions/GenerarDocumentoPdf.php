<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\FirmasDelDocumento;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Ui\CodigoQr;
use App\Shared\Ui\Formato;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class GenerarDocumentoPdf
{
    public function __invoke(int $documentoId): PdfBuilder
    {
        $documento = Documento::query()->findOrFail($documentoId);

        if (! $documento->estaVigente()) {
            throw new ReglaDeNegocioIncumplida("El documento {$documento->codigo} está anulado; ya no se emite su PDF.");
        }

        $verificacion = route('verificacion.documento', $documento->codigo);
        $emitido = $documento->emitido_en->setTimezone('America/Lima');
        $pdf = Pdf::view($documento->tipo->vistaPdf(), [
            'documento' => $documento,
            'verificacion' => $verificacion,
            'qr' => CodigoQr::comoDataUri($verificacion),
            'emitidoEl' => Formato::fechaCorta($emitido),
            'lugarYFecha' => 'Huaraz, '.Formato::fechaLarga($emitido),
            'firmas' => $documento->detalle['firmas'] ?? FirmasDelDocumento::de($documento->tipo->grupoDeFirma(), $emitido),
        ])->format('a4');

        // A certificate is a landscape page like a diploma; constancias are letters.
        return ($documento->tipo === TipoDocumento::CertificadoNivel ? $pdf->landscape() : $pdf)->inline($documento->tipo->archivoPdf($documento->codigo));
    }
}
