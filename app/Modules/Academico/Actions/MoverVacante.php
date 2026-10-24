<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\TrasladoDeVacante;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

final readonly class MoverVacante implements TrasladoDeVacante
{
    private const array ESTADOS_QUE_RECIBEN = [EstadoGrupo::Abierto, EstadoGrupo::Cerrado];

    public function __invoke(int $grupoOrigenId, int $grupoDestinoId): void
    {
        if ($grupoOrigenId === $grupoDestinoId) {
            throw new ReglaDeNegocioIncumplida('El grupo destino debe ser distinto del grupo actual.');
        }

        DB::transaction(function () use ($grupoOrigenId, $grupoDestinoId): void {
            // Ascending id order, so two transfers in opposite directions cannot deadlock.
            $grupos = Grupo::query()->whereKey([$grupoOrigenId, $grupoDestinoId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $origen = $grupos->get($grupoOrigenId) ?? throw new ReglaDeNegocioIncumplida('El grupo actual no existe.');
            $destino = $grupos->get($grupoDestinoId) ?? throw new ReglaDeNegocioIncumplida('El grupo destino no existe.');

            $this->asegurarDestino($origen, $destino);

            $origen->decrement('matriculados');
            $destino->increment('matriculados');
        });
    }

    private function asegurarDestino(Grupo $origen, Grupo $destino): void
    {
        if ($origen->estado->esTerminal()) {
            throw new ReglaDeNegocioIncumplida("El grupo {$origen->codigo} está {$origen->estado->etiqueta()} y ya no admite traslados.");
        }

        if ($origen->periodo_id !== $destino->periodo_id || $origen->ciclo_id !== $destino->ciclo_id) {
            throw new ReglaDeNegocioIncumplida('El grupo destino debe ser del mismo periodo y ciclo.');
        }

        if (! in_array($destino->estado, self::ESTADOS_QUE_RECIBEN, true)) {
            throw new ReglaDeNegocioIncumplida("El grupo {$destino->codigo} está {$destino->estado->etiqueta()} y no recibe traslados.");
        }

        if ($destino->vacantes_disponibles < 1) {
            throw new ReglaDeNegocioIncumplida("El grupo {$destino->codigo} no tiene vacantes disponibles.");
        }
    }
}
