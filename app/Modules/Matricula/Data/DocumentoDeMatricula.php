<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Pagos\Data\DetalleDePago;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Personas\Data\FichaDeEstudiante;
use Carbon\CarbonImmutable;

final readonly class DocumentoDeMatricula
{
    public function __construct(
        public string $codigo,
        public FichaDeEstudiante $estudiante,
        public FichaDeGrupo $grupo,
        public ?DetalleDePago $pago,
        public CondicionMatricula $condicion,
        public EstadoMatricula $estado,
        public CarbonImmutable $matriculadoEn,
        public ?string $docente = null,
    ) {}

    // The TdR names a validated payment "Verificado"; any later state, such as a voided payment, is shown as it is.
    public function estadoDelPago(): string
    {
        return match (true) {
            ! $this->pago instanceof DetalleDePago => 'Sin pago registrado',
            $this->pago->estado === EstadoPago::Validado => 'Verificado',
            default => $this->pago->estado->etiqueta(),
        };
    }
}
