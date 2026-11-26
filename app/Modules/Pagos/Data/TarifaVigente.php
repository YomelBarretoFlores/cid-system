<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Pagos\Enums\TipoConcepto;
use Carbon\CarbonImmutable;

final readonly class TarifaVigente
{
    public function __construct(
        public string $codigo,
        public string $concepto,
        public string $fuente,
        public string $fuenteEtiqueta,
        public string $tipoVinculo,
        public string $vinculoEtiqueta,
        public string $monto,
        public CarbonImmutable $vigenteDesde,
        public TipoConcepto $tipo,
        public ?NivelCurricular $nivel = null,
        public ?string $programa = null,
    ) {}

    /** "Nivel Básico · Posgrado", or empty for a fee that applies to every level and program. */
    public function alcance(): string
    {
        return implode(' · ', array_filter([$this->nivel instanceof NivelCurricular ? "Nivel {$this->nivel->etiqueta()}" : null, $this->programa]));
    }
}
