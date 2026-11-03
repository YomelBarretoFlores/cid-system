<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Database\QueryException;

final readonly class EliminarTarifa
{
    public const string CON_PAGOS = 'La tarifa ya tiene pagos y no puede eliminarse.';

    private const string FOREIGN_KEY_VIOLATION = '23503';

    public function __invoke(int $tarifaId): void
    {
        try {
            Transaccion::ejecutar(fn () => $this->eliminar($tarifaId));
        } catch (QueryException $error) {
            // A payment committed after the re-check still hits the foreign key.
            throw $error->getCode() === self::FOREIGN_KEY_VIOLATION ? new ReglaDeNegocioIncumplida(self::CON_PAGOS, previous: $error) : $error;
        }
    }

    private function eliminar(int $tarifaId): void
    {
        $conceptoId = Tarifa::query()->whereKey($tarifaId)->value('concepto_id');

        if ($conceptoId === null) {
            return;
        }

        // Concept, then fee: the order RegistrarPago and RegistrarTarifa lock them.
        Concepto::query()->whereKey($conceptoId)->lockForUpdate()->firstOrFail();
        $tarifa = Tarifa::query()->whereKey($tarifaId)->lockForUpdate()->first();

        if ($tarifa === null) {
            return;
        }

        if (Pago::query()->where('tarifa_id', $tarifa->id)->exists()) {
            throw new ReglaDeNegocioIncumplida(self::CON_PAGOS);
        }

        $tarifa->delete();
    }
}
