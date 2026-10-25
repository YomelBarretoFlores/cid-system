<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class DocenteDelActa
{
    public function __construct(private DocenteDelGrupo $docenteDelGrupo) {}

    public function asegurar(int $grupoId, string $grupoCodigo, int $docenteId): void
    {
        if (($this->docenteDelGrupo)($grupoId)?->docenteId !== $docenteId) {
            throw new ReglaDeNegocioIncumplida("No tienes a cargo el grupo {$grupoCodigo}.");
        }
    }
}
