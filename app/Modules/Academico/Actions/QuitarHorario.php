<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Support\HorarioModificable;
use App\Shared\Support\Database\Transaccion;

final readonly class QuitarHorario
{
    public function __construct(private HorarioModificable $horarioModificable) {}

    public function __invoke(int $grupoId, int $horarioId): void
    {
        Transaccion::ejecutar(function () use ($grupoId, $horarioId): void {
            $grupo = Grupo::query()->whereKey($grupoId)->lockForUpdate()->firstOrFail();
            $horario = Horario::query()->where('grupo_id', $grupo->id)->find($horarioId);

            if ($horario === null) {
                return;
            }

            $this->horarioModificable->asegurar($grupo);
            $horario->delete();
        });
    }
}
