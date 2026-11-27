<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\NotasCalculadas;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Modules\Docencia\Data\CodigoDeDocente;
use App\Shared\Ui\Formato;
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
        public ?int $cicloNumeroHasta = null,
        public ?string $cicloNombreHasta = null,
    ) {}

    public static function desde(Acta $acta): self
    {
        $componentes = $acta->componentesDeEvaluacion();

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
            componentes: $componentes,
            filas: array_values($acta->notas->sortBy('estudiante_nombre')->map(fn (NotaFinal $nota): FilaDelActa => new FilaDelActa(
                $nota->matricula_id, $nota->persona_id, $nota->estudiante_nombre, $nota->estudiante_documento,
                $nota->notas, $nota->promedio, $nota->estado_final, NotasCalculadas::de($componentes, $nota->notas), $nota->promedio_hasta, $nota->estado_final_hasta,
            ))->all()),
            validadaEn: $acta->validada_en?->setTimezone('America/Lima'),
            cicloNumeroHasta: $acta->ciclo_numero_hasta,
            cicloNombreHasta: $acta->ciclo_nombre_hasta,
        );
    }

    /**
     * The columns shown before the cycle grade; a calculated cycle grade is shown once, from the stored average.
     *
     * @return list<ComponenteDeEvaluacion>
     */
    public function columnas(): array
    {
        return array_values(array_filter($this->componentes, fn (ComponenteDeEvaluacion $componente): bool => $componente->ciclo === null || $componente->esIngresada()));
    }

    /**
     * @return list<ComponenteDeEvaluacion>
     */
    public function columnasIngresadas(): array
    {
        return array_values(array_filter($this->componentes, fn (ComponenteDeEvaluacion $componente): bool => $componente->esIngresada()));
    }

    public function esAcelerada(): bool
    {
        return $this->cicloNumeroHasta !== null;
    }

    public function ciclos(): string
    {
        return $this->cicloNombreHasta === null ? $this->cicloNombre : "{$this->cicloNombre} y {$this->cicloNombreHasta}";
    }

    public function numerosDeCiclo(): string
    {
        return $this->cicloNumeroHasta === null ? Formato::romano($this->cicloNumero) : Formato::romano($this->cicloNumero).' y '.Formato::romano($this->cicloNumeroHasta);
    }

    public function docenteCodigo(): string
    {
        return CodigoDeDocente::de($this->docenteId);
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
