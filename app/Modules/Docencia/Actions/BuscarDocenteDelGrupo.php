<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Modules\Docencia\Data\DocenteAsignado;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Personas\Contracts\FichaDePersona;

final readonly class BuscarDocenteDelGrupo implements DocenteDelGrupo
{
    public function __construct(private FichaDePersona $fichaDePersona) {}

    public function __invoke(int $grupoId): ?DocenteAsignado
    {
        $carga = CargaAcademica::query()
            ->join('doc_docentes', 'doc_docentes.id', '=', 'doc_cargas_academicas.docente_id')
            ->where('doc_cargas_academicas.grupo_id', $grupoId)
            ->first(['doc_cargas_academicas.docente_id', 'doc_docentes.persona_id']);

        if ($carga === null) {
            return null;
        }

        return new DocenteAsignado((int) $carga->docente_id, ($this->fichaDePersona)((int) $carga->getAttribute('persona_id'))->nombreCompleto);
    }
}
