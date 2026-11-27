<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Academico\Contracts\CursoDelGrupo;
use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\DatosDelCurso;
use App\Modules\Academico\Data\EsquemaDeEvaluacion;
use App\Modules\Certificacion\Data\ActaDelGrupo;
use App\Modules\Certificacion\Data\FilaDelActa;
use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Modules\Docencia\Contracts\NotasParcialesDelGrupo;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Modules\Personas\Contracts\FichasDePersonas;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Builds the acta a group would close with now: its active students and their pre-acta grades under the group's current scheme.
final readonly class ActaPrevia
{
    public function __construct(
        private CursoDelGrupo $cursoDelGrupo,
        private DocenteDelGrupo $docenteDelGrupo,
        private EsquemaDelGrupo $esquemaDelGrupo,
        private EstudiantesDelGrupo $estudiantesDelGrupo,
        private NotasParcialesDelGrupo $notasParcialesDelGrupo,
        private FichasDePersonas $fichasDePersonas,
    ) {}

    public function armar(int $grupoId): ActaDelGrupo
    {
        $curso = $this->curso($grupoId);
        $docente = ($this->docenteDelGrupo)($grupoId)
            ?? throw new ReglaDeNegocioIncumplida("El grupo {$curso->codigo} no tiene docente a cargo.");
        $esquema = ($this->esquemaDelGrupo)($grupoId)
            ?? throw new ReglaDeNegocioIncumplida("El grupo {$curso->codigo} no tiene un sistema de evaluación vigente; pide a coordinación que lo registre.");

        return new ActaDelGrupo(
            grupoId: $grupoId, actaId: null, estado: null, grupoCodigo: $curso->codigo, idioma: $curso->idioma, nivel: $curso->nivel,
            cicloNumero: $curso->cicloNumero, cicloNombre: $curso->cicloNombre, periodo: $curso->periodo,
            docenteId: $docente->docenteId, docente: $docente->nombreCompleto, esquemaId: $esquema->esquemaId, esquema: $esquema->nombre,
            notaMaxima: $esquema->notaMaxima, notaMinimaAprobatoria: $esquema->notaMinimaAprobatoria,
            componentes: $esquema->componentes, filas: $this->filas($grupoId, $esquema, $curso->cicloNumeroHasta !== null), validadaEn: null,
            cicloNumeroHasta: $curso->cicloNumeroHasta, cicloNombreHasta: $curso->cicloNombreHasta,
        );
    }

    public function curso(int $grupoId): DatosDelCurso
    {
        return ($this->cursoDelGrupo)($grupoId) ?? throw new ReglaDeNegocioIncumplida('El grupo no existe.');
    }

    /**
     * @return list<FilaDelActa>
     */
    private function filas(int $grupoId, EsquemaDeEvaluacion $esquema, bool $acelerado): array
    {
        $estudiantes = ($this->estudiantesDelGrupo)($grupoId);
        $fichas = ($this->fichasDePersonas)(array_map(fn (EstudianteDelGrupo $estudiante): int => $estudiante->personaId, $estudiantes));
        $notas = ($this->notasParcialesDelGrupo)($grupoId);
        $vigentes = array_flip(array_map(fn (ComponenteDeEvaluacion $componente): int => $componente->id, $esquema->componentes));

        return array_map(function (EstudianteDelGrupo $estudiante) use ($fichas, $notas, $vigentes, $esquema, $acelerado): FilaDelActa {
            $propias = array_intersect_key($notas[$estudiante->matriculaId] ?? [], $vigentes);
            [$promedio, $estado, $calculadas] = PromedioFinal::calcular($propias, $esquema->componentes, $esquema->notaMinimaAprobatoria);
            [$promedioHasta, $estadoHasta] = $acelerado ? PromedioFinal::delSegundoCiclo($calculadas, $esquema->notaMinimaAprobatoria) : [null, null];

            return new FilaDelActa(
                $estudiante->matriculaId, $estudiante->personaId, $estudiante->nombreCompleto, $fichas[$estudiante->personaId]->documento ?? '',
                $propias, $promedio, $estado, $calculadas, $promedioHasta, $estadoHasta,
            );
        }, $estudiantes);
    }
}
