<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\NotaFinal;
use Carbon\CarbonImmutable;

// The final grade sheet of a group: stored when it was closed, otherwise a preview computed from the pre-acta (estado null).
final readonly class ActaDelGrupo
{
    /**
     * @param  list<ComponenteDeEvaluacion>  $componentes
     * @param  list<FilaDelActa>  $filas
     */
    public function __construct(
        public int $grupoId,
        public ?int $actaId,
        public ?EstadoActa $estado,
        public string $grupoCodigo,
        public string $idioma,
        public NivelCurricular $nivel,
        public int $cicloNumero,
        public string $cicloNombre,
        public string $periodo,
        public int $docenteId,
        public string $docente,
        public int $esquemaId,
        public string $esquema,
        public string $notaMaxima,
        public string $notaMinimaAprobatoria,
        public array $componentes,
        public array $filas,
        public ?CarbonImmutable $validadaEn,
    ) {}

    public static function desde(Acta $acta): self
    {
        return new self(
            grupoId: $acta->grupo_id,
            actaId: $acta->id,
            estado: $acta->estado,
            grupoCodigo: $acta->grupo_codigo,
            idioma: $acta->idioma,
            nivel: $acta->nivel,
            cicloNumero: $acta->ciclo_numero,
            cicloNombre: $acta->ciclo_nombre,
            periodo: $acta->periodo,
            docenteId: $acta->docente_id,
            docente: $acta->docente_nombre,
            esquemaId: $acta->esquema_id,
            esquema: $acta->esquema_nombre,
            notaMaxima: $acta->nota_maxima,
            notaMinimaAprobatoria: $acta->nota_minima_aprobatoria,
            componentes: $acta->componentesDeEvaluacion(),
            filas: array_values($acta->notas->sortBy('estudiante_nombre')->map(fn (NotaFinal $nota): FilaDelActa => new FilaDelActa(
                $nota->matricula_id, $nota->persona_id, $nota->estudiante_nombre, $nota->estudiante_documento,
                $nota->notas, $nota->promedio, $nota->estado_final,
            ))->all()),
            validadaEn: $acta->validada_en,
        );
    }

    public function estaCerrada(): bool
    {
        return $this->estado === EstadoActa::Cerrada;
    }

    public function admiteRectificacion(): bool
    {
        return $this->estado === EstadoActa::Abierta;
    }
}
