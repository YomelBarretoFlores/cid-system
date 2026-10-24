<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\GruposParaTraslado;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;

final readonly class ListarGruposParaTraslado implements GruposParaTraslado
{
    public function __invoke(int $grupoId): array
    {
        $origen = Grupo::query()->whereKey($grupoId)->firstOrFail();
        $estados = array_values(array_filter(EstadoGrupo::cases(), fn (EstadoGrupo $estado): bool => $estado->recibeTraslados()));

        return Grupo::query()
            ->where(['periodo_id' => $origen->periodo_id, 'ciclo_id' => $origen->ciclo_id])
            ->whereKeyNot($origen->id)
            ->whereIn('estado', $estados)
            ->whereColumn('matriculados', '<', 'cupo_maximo')
            ->orderBy('codigo')
            ->get()
            ->mapWithKeys(fn (Grupo $grupo): array => [$grupo->id => "{$grupo->codigo} · {$grupo->vacantes_disponibles} vacantes"])
            ->all();
    }
}
