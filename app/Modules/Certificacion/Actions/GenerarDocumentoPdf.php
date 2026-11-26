<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Contracts\DatosInstitucionales;
use App\Modules\Certificacion\Contracts\DenominacionDelAnio;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\FirmasDelDocumento;
use App\Modules\Personas\Contracts\FotoDePersona;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Ui\CodigoQr;
use App\Shared\Ui\Formato;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class GenerarDocumentoPdf
{
    public function __construct(private DenominacionDelAnio $denominacionDelAnio, private DatosInstitucionales $datosInstitucionales, private FotoDePersona $fotoDePersona) {}

    public function __invoke(int $documentoId): PdfBuilder
    {
        $documento = Documento::query()->findOrFail($documentoId);

        if (! $documento->estaVigente()) {
            throw new ReglaDeNegocioIncumplida("El documento {$documento->codigo} está anulado; ya no se emite su PDF.");
        }

        $verificacion = route('verificacion.documento', $documento->codigo);
        $emitido = $documento->emitido_en->setTimezone('America/Lima');
        $institucion = ($this->datosInstitucionales)();
        $esCertificado = $documento->tipo === TipoDocumento::CertificadoNivel;
        $vista = $documento->esConstanciaPorNivel() ? 'certificacion::pdf.constancia-de-notas-por-nivel' : $documento->tipo->vistaPdf();
        $pdf = Pdf::view($vista, [
            'documento' => $documento,
            'verificacion' => $verificacion,
            'qr' => CodigoQr::comoDataUri($verificacion),
            'emitidoEl' => Formato::fechaCorta($emitido),
            'lugarYFecha' => "{$institucion->ciudad}, ".Formato::fechaLarga($emitido),
            'institucion' => $institucion,
            // Read when printing, not frozen: a photo is too heavy to copy into every document.
            'foto' => $esCertificado ? ($this->fotoDePersona)((int) $documento->getAttribute('persona_id')) : null,
            'firmas' => $documento->detalle['firmas'] ?? FirmasDelDocumento::de($documento->tipo->grupoDeFirma(), $emitido),
            // Documents issued before the denomination existed take the one of their issue year.
            'denominacion' => array_key_exists('denominacion', $documento->detalle ?? []) ? $documento->detalle['denominacion'] : ($this->denominacionDelAnio)((int) $emitido->format('Y')),
        ])->format('a4');

        // A certificate is a landscape page like a diploma; constancias are letters.
        return ($esCertificado ? $pdf->landscape() : $pdf)->inline($documento->tipo->archivoPdf($documento->codigo));
    }
}
