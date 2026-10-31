<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

use App\Modules\Docencia\Enums\ConfirmacionDeClase;
use Carbon\CarbonImmutable;

final readonly class ClaseParaConfirmar
{
    public function __construct(
        public int $claseId,
        public CarbonImmutable $fecha,
        public string $horario,
        public string $grupo,
        public string $docente,
        public int $presentes,
        public int $estudiantes,
        public CarbonImmutable $vence,
        public bool $puedeConfirmar,
        public ConfirmacionDeClase $confirmacion,
        public ?string $observacion,
    ) {}
}
