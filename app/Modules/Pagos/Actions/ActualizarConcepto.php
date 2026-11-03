<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Data\DatosDeConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Services check a payment's concept type when they are used, so once a payment exists the type is part of what it paid for.
final readonly class ActualizarConcepto
{
    public function __invoke(int $conceptoId, DatosDeConcepto $datos): Concepto
    {
        return Transaccion::ejecutar(function () use ($conceptoId, $datos): Concepto {
            // The row lock also waits for a payment being registered for this concept: its foreign key check holds a key-share lock.
            $concepto = Concepto::query()->whereKey($conceptoId)->lockForUpdate()->firstOrFail();

            if ($concepto->tipo !== $datos->tipo && Pago::query()->where('concepto_id', $concepto->id)->exists()) {
                throw new ReglaDeNegocioIncumplida("El concepto {$concepto->nombre} ya tiene pagos: el servicio que habilita no puede cambiar.");
            }

            $concepto->update([
                'nombre' => $datos->nombre,
                'tipo' => $datos->tipo,
                'fuente_id' => $datos->fuenteId,
                'activo' => $datos->activo,
            ]);

            return $concepto;
        });
    }
}
