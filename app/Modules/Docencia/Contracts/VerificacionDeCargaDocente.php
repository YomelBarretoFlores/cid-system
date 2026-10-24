<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Contracts;

interface VerificacionDeCargaDocente
{
    /** Called with the group row already locked; refuses a schedule its assigned teacher cannot teach. */
    public function __invoke(int $grupoId): void;
}
