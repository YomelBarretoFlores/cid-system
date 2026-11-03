<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\RegistroDeMovimiento;
use App\Shared\Support\Database\Transaccion;

// A voided fee no longer pays for an unfinished trámite: it goes back to waiting for a payment. A finished or closed one stays as it is.
final readonly class RevertirPagoAnulado
{
    public const string COMENTARIO = 'El CILN anuló el pago aplicado a este trámite. Registra un nuevo pago de la tasa para continuar.';

    public function __invoke(int $pagoId): ?Tramite
    {
        $personaId = Tramite::query()->where('pago_id', $pagoId)->value('persona_id');

        if ($personaId === null) {
            return null;
        }

        return Transaccion::ejecutar(function () use ($pagoId, $personaId): ?Tramite {
            RegistroDeMovimiento::bloquearPersona((int) $personaId);

            $tramite = Tramite::query()->where('pago_id', $pagoId)->lockForUpdate()->first();

            if ($tramite === null || $tramite->estado->terminado()) {
                return null;
            }

            $tramite->update(['pago_id' => null, 'estado' => EstadoTramite::PendientePago, 'observacion' => null, 'etapa_actual' => 0]);
            RegistroDeMovimiento::registrar($tramite, comentario: self::COMENTARIO);

            return $tramite;
        });
    }
}
