<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Data\ActaDelGrupo;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\GrupoDeFirma;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Support\FirmasDelDocumento;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class GenerarActaFinalPdf
{
    public function __invoke(int $actaId): PdfBuilder
    {
        $acta = Acta::query()->with('notas')->findOrFail($actaId);

        if ($acta->estado !== EstadoActa::Cerrada) {
            throw new ReglaDeNegocioIncumplida("El acta final del grupo {$acta->grupo_codigo} está en rectificación; su PDF se emite cuando se vuelva a cerrar.");
        }

        $datos = ActaDelGrupo::desde($acta);
        $firmas = [['nombre' => $datos->docente, 'cargo' => 'Docente del curso'], ...FirmasDelDocumento::de(GrupoDeFirma::Actas, ($datos->validadaEn ?? now())->setTimezone('America/Lima'))];

        return Pdf::view('certificacion::pdf.acta-final', ['acta' => $datos, 'codigo' => sprintf('ACTA-%06d', $acta->id), 'firmas' => $firmas])
            ->format('a4')
            ->landscape()
            ->inline("acta-final-{$acta->grupo_codigo}.pdf");
    }
}
