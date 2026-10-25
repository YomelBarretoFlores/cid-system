<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Matricula\Contracts\MovimientoPorTramite;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class MoverMatriculaPorTramite implements MovimientoPorTramite
{
    public function __construct(
        private TrasladarMatricula $trasladarMatricula,
        private RetirarMatricula $retirarMatricula,
    ) {}

    public function __invoke(int $matriculaId, ?int $grupoDestinoId, string $motivo, User $autorizadoPor): void
    {
        throw_unless($autorizadoPor->can(PermisoMatricula::Gestionar->value), ReglaDeNegocioIncumplida::class, 'No tiene permiso para trasladar ni retirar matrículas.');

        if ($grupoDestinoId === null) {
            ($this->retirarMatricula)($matriculaId, $motivo, $autorizadoPor);

            return;
        }

        ($this->trasladarMatricula)($matriculaId, $grupoDestinoId, $motivo, $autorizadoPor);
    }
}
