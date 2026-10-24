<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\LiberacionDeVacante;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\TipoMovimiento;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\Movimiento;
use App\Modules\Matricula\Support\MovimientoDeMatricula;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;

final readonly class RetirarMatricula
{
    public function __construct(private LiberacionDeVacante $liberacionDeVacante) {}

    public function __invoke(int $matriculaId, string $motivo, User $autorizadoPor): Matricula
    {
        $motivo = MovimientoDeMatricula::motivo($motivo);

        return Transaccion::ejecutar(function () use ($matriculaId, $motivo, $autorizadoPor): Matricula {
            $matricula = MovimientoDeMatricula::bloquearMatriculaActiva($matriculaId);

            ($this->liberacionDeVacante)($matricula->grupo_id);

            Movimiento::query()->create([
                'matricula_id' => $matricula->id,
                'tipo' => TipoMovimiento::Retiro,
                'grupo_origen_id' => $matricula->grupo_id,
                'motivo' => $motivo,
                'autorizado_por' => $autorizadoPor->id,
                'efectuado_en' => now(),
            ]);

            $matricula->update(['estado' => EstadoMatricula::Retirada, 'retirado_en' => now()]);

            return $matricula;
        });
    }
}
