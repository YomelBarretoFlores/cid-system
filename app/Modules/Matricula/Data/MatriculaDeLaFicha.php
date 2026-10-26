<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Pagos\Data\DetalleDePago;
use App\Modules\Pagos\Enums\EstadoPago;
use Carbon\CarbonImmutable;

final readonly class MatriculaDeLaFicha
{
    public function __construct(
        public FichaDeGrupo $grupo,
        public CondicionMatricula $condicion,
        public EstadoMatricula $estado,
        public CarbonImmutable $matriculadoEn,
        public ?DetalleDePago $pago = null,
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

    // Only an active enrollment with a verified payment adds to what the student paid for the period.
    public function centimosPagados(): int
    {
        if ($this->estado !== EstadoMatricula::Activa || $this->pago?->estado !== EstadoPago::Validado) {
            return 0;
        }

        [$soles, $decimales] = explode('.', $this->pago->monto.'.');

        return (int) $soles * 100 + (int) str_pad(substr($decimales, 0, 2), 2, '0');
    }
}
