<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Enums\FormaDeAprobacion;

// A cycle passed without an acta (placement, proficiency exam) has no acta, enrollment, group or period.
final readonly class NotaDelHistorial
{
    public function __construct(
        public ?int $actaId,
        public string $idioma,
        public NivelCurricular $nivel,
        public int $cicloNumero,
        public string $cicloNombre,
        public int $horasLectivas,
        public ?string $periodo,
        public ?string $promedio,
        public EstadoFinal $estadoFinal,
        public ?int $matriculaId,
        public ?string $grupoCodigo,
        public FormaDeAprobacion $forma = FormaDeAprobacion::Matricula,
        public ?int $anio = null,
        public ?int $mes = null,
        // Day the result became official (acta validation or approval date), Y-m-d in Lima; not part of the frozen detail.
        public ?string $fecha = null,
    ) {}

    /**
     * @return array{ciclo_numero: int, ciclo_nombre: string, nivel: string, periodo: ?string, horas_lectivas: int, promedio: ?string, estado_final: string, forma: string, anio: ?int, mes: ?int}
     */
    public function comoDetalle(): array
    {
        return [
            'ciclo_numero' => $this->cicloNumero, 'ciclo_nombre' => $this->cicloNombre, 'nivel' => $this->nivel->value,
            'periodo' => $this->periodo, 'horas_lectivas' => $this->horasLectivas, 'promedio' => $this->promedio, 'estado_final' => $this->estadoFinal->value,
            'forma' => $this->forma->value, 'anio' => $this->anio, 'mes' => $this->mes,
        ];
    }
}
