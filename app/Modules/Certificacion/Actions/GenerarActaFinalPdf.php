<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Contracts\DenominacionDelAnio;
use App\Modules\Certificacion\Data\ActaDelGrupo;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\GrupoDeFirma;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Support\FirmasDelDocumento;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Ui\CodigoQr;
use Illuminate\Support\Facades\URL;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class GenerarActaFinalPdf
{
    public function __construct(private DenominacionDelAnio $denominacionDelAnio) {}

    public function __invoke(int $actaId): PdfBuilder
    {
        $acta = Acta::query()->with('notas')->findOrFail($actaId);

        if ($acta->estado !== EstadoActa::Cerrada) {
            throw new ReglaDeNegocioIncumplida("El acta final del grupo {$acta->grupo_codigo} está en rectificación; su PDF se emite cuando se vuelva a cerrar.");
        }

        $datos = ActaDelGrupo::desde($acta);
        $validada = ($datos->validadaEn ?? now())->setTimezone('America/Lima');
        $firmas = [['nombre' => $datos->docente, 'cargo' => 'Docente del curso'], ...FirmasDelDocumento::de(GrupoDeFirma::Actas, $validada)];

        // A signed link: acta numbers are sequential, so an unsigned URL would let anyone walk through every acta. Only the path is signed and the
        // public address is put in front, so a printed QR keeps working if the domain or the proxy setup changes.
        $verificacion = rtrim((string) config('app.url'), '/').URL::signedRoute('verificacion.acta', ['acta' => $acta->id], absolute: false);

        return Pdf::view('certificacion::pdf.acta-final', [
            'acta' => $datos,
            'verificacion' => $verificacion,
            'qr' => CodigoQr::comoDataUri($verificacion),
            'codigo' => sprintf('ACTA-%06d', $acta->id),
            'firmas' => $firmas,
            'denominacion' => ($this->denominacionDelAnio)((int) $validada->format('Y')),
        ])
            ->format('a4')
            ->landscape()
            ->inline("acta-final-{$acta->grupo_codigo}.pdf");
    }
}
