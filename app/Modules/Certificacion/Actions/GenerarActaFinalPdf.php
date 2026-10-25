<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Data\ActaDelGrupo;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Models\Acta;
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

        return Pdf::view('certificacion::pdf.acta-final', ['acta' => ActaDelGrupo::desde($acta), 'codigo' => sprintf('ACTA-%06d', $acta->id)])
            ->format('a4')
            ->landscape()
            ->inline("acta-final-{$acta->grupo_codigo}.pdf");
    }
}
