<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Events\PagoValidado;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Support\AlertasDePago;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Config;

/** The cashier validates only with the original paper voucher in hand, so a forged or generated image cannot unlock services. */
final readonly class ValidarPago
{
    public function __invoke(int $pagoId, User $validador, bool $boletaOriginalVerificada, ?string $justificacionMonto = null): Pago
    {
        $justificacion = trim((string) preg_replace('/\s+/u', ' ', (string) $justificacionMonto));

        return Transaccion::ejecutar(function () use ($pagoId, $validador, $boletaOriginalVerificada, $justificacion): Pago {
            $pago = Pago::query()->whereKey($pagoId)->lockForUpdate()->firstOrFail();

            if ($pago->estado === EstadoPago::Validado) {
                return $pago;
            }

            $pago->estado->asegurarTransicionA(EstadoPago::Validado);
            $this->asegurarOtraPersona($pago, $validador);

            if (! $boletaOriginalVerificada) {
                throw new ReglaDeNegocioIncumplida('Confirma que tienes la boleta original y que coincide con el número, la fecha y el monto registrados.');
            }

            $pago->update([
                'estado' => EstadoPago::Validado,
                'validado_por' => $validador->id,
                'validado_en' => now(),
                'boleta_original_verificada_por' => $validador->id,
                'boleta_original_verificada_en' => now(),
                'justificacion_monto' => $this->justificacion($pago, $justificacion),
            ]);

            PagoValidado::dispatch($pago->id);

            return $pago;
        });
    }

    private function asegurarOtraPersona(Pago $pago, User $validador): void
    {
        if (Config::boolean('ciln.pagos.validacion_por_otra_persona') && $pago->registrado_por === $validador->id) {
            throw new ReglaDeNegocioIncumplida('No puedes validar un pago que registraste tú; debe validarlo otra persona del CILN.');
        }
    }

    private function justificacion(Pago $pago, string $justificacion): ?string
    {
        $diferencia = AlertasDePago::diferenciaDeMonto($pago);

        if ($diferencia === null) {
            return null;
        }

        return $justificacion !== '' ? $justificacion : throw new ReglaDeNegocioIncumplida("{$diferencia} Escribe la justificación para validarlo.");
    }
}
