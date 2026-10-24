<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Matricula\Data\DocumentoDeMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Contracts\ConsultaDePago;
use App\Modules\Personas\Contracts\FichaDePersona;

final readonly class ComponerDocumentoDeMatricula
{
    public function __construct(
        private FichaDePersona $fichaDePersona,
        private DetalleDeGrupos $detalleDeGrupos,
        private ConsultaDePago $consultaDePago,
        private DocentesDeGrupos $docentesDeGrupos,
    ) {}

    public function __invoke(Matricula $matricula): DocumentoDeMatricula
    {
        return new DocumentoDeMatricula(
            codigo: $matricula->ulid,
            estudiante: ($this->fichaDePersona)($matricula->persona_id),
            grupo: ($this->detalleDeGrupos)([$matricula->grupo_id])[$matricula->grupo_id],
            pago: $matricula->pago_id === null ? null : ($this->consultaDePago)($matricula->pago_id),
            condicion: $matricula->condicion,
            estado: $matricula->estado,
            matriculadoEn: $matricula->matriculado_en->setTimezone('America/Lima'),
            docente: ($this->docentesDeGrupos)([$matricula->grupo_id])[$matricula->grupo_id] ?? null,
        );
    }
}
