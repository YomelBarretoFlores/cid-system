<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;

// Teacher pages answer 404 for any group the signed-in teacher is not in charge of, so group ids reveal nothing.
final readonly class AccesoDelDocente
{
    public function __construct(
        private DocenteDelUsuario $docenteDelUsuario,
        private DocenteDelGrupo $docenteDelGrupo,
    ) {}

    public function docenteId(int $userId): int
    {
        return ($this->docenteDelUsuario)($userId) ?? abort(403);
    }

    public function asegurar(int $userId, int $grupoId): int
    {
        $docenteId = $this->docenteId($userId);
        abort_unless(($this->docenteDelGrupo)($grupoId)?->docenteId === $docenteId, 404);

        return $docenteId;
    }
}
