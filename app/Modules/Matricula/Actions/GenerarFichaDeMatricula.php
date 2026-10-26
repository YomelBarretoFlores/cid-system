<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Matricula\Models\Matricula;
use Spatie\LaravelPdf\PdfBuilder;

// An enrollment opens the record of its whole period, like the UNASAM SGA record: one sheet per student and period.
final readonly class GenerarFichaDeMatricula
{
    public function __construct(
        private DetalleDeGrupos $detalleDeGrupos,
        private GenerarFichaDelPeriodo $generar,
    ) {}

    public function __invoke(Matricula $matricula): PdfBuilder
    {
        return ($this->generar)($matricula->persona_id, ($this->detalleDeGrupos)([$matricula->grupo_id])[$matricula->grupo_id]->periodoId);
    }
}
