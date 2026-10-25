<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Matricula\Contracts\MovimientoPorTramite;
use App\Shared\Auth\Models\User;

final readonly class MoverMatriculaPorTramite implements MovimientoPorTramite
{
    public function __construct(
        private TrasladarMatricula $trasladarMatricula,
        private RetirarMatricula $retirarMatricula,
    ) {}

    public function __invoke(int $matriculaId, ?int $grupoDestinoId, string $motivo, User $autorizadoPor): void
    {
        if ($grupoDestinoId === null) {
            ($this->retirarMatricula)($matriculaId, $motivo, $autorizadoPor);

            return;
        }

        ($this->trasladarMatricula)($matriculaId, $grupoDestinoId, $motivo, $autorizadoPor);
    }
}
