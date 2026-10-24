<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\TrasladoDeVacante;
use App\Modules\Matricula\Enums\TipoMovimiento;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\Movimiento;
use App\Modules\Matricula\Support\MovimientoDeMatricula;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;

final readonly class TrasladarMatricula
{
    public function __construct(private TrasladoDeVacante $trasladoDeVacante) {}

    public function __invoke(int $matriculaId, int $grupoDestinoId, string $motivo, User $autorizadoPor): Matricula
    {
        $motivo = MovimientoDeMatricula::motivo($motivo);

        return Transaccion::ejecutar(function () use ($matriculaId, $grupoDestinoId, $motivo, $autorizadoPor): Matricula {
            $matricula = MovimientoDeMatricula::bloquearMatriculaActiva($matriculaId);
            $grupoOrigenId = $matricula->grupo_id;

            ($this->trasladoDeVacante)($grupoOrigenId, $grupoDestinoId);

            Movimiento::query()->create([
                'matricula_id' => $matricula->id,
                'tipo' => TipoMovimiento::Traslado,
                'grupo_origen_id' => $grupoOrigenId,
                'grupo_destino_id' => $grupoDestinoId,
                'motivo' => $motivo,
                'autorizado_por' => $autorizadoPor->id,
                'efectuado_en' => now(),
            ]);

            $matricula->update(['grupo_id' => $grupoDestinoId]);

            return $matricula;
        });
    }
}
