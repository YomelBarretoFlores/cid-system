<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

use App\Modules\Academico\Enums\NivelCurricular;

final readonly class NivelPorCertificar
{
    public function __construct(
        public string $idioma,
        public NivelCurricular $nivel,
    ) {}

    public function clave(): string
    {
        return "{$this->idioma}|{$this->nivel->value}";
    }

    public function etiqueta(): string
    {
        return "{$this->idioma} · {$this->nivel->etiqueta()}";
    }
}
