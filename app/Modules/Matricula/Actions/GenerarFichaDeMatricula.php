<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Matricula\Models\Matricula;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

// An enrollment opens the record of its whole period, like the UNASAM SGA record: one sheet per student and period.
final readonly class GenerarFichaDeMatricula
{
    public function __construct(
        private DetalleDeGrupos $detalleDeGrupos,
        private ComponerFichaDelPeriodo $componer,
    ) {}

    public function __invoke(Matricula $matricula): PdfBuilder
    {
        $periodoId = ($this->detalleDeGrupos)([$matricula->grupo_id])[$matricula->grupo_id]->periodoId;
        $ficha = ($this->componer)($matricula->persona_id, $periodoId);

        return Pdf::view('matricula::pdf.ficha', ['ficha' => $ficha])
            ->format('a4')
            ->inline('ficha-de-matricula-'.Str::slug($ficha->periodo).'.pdf');
    }
}
