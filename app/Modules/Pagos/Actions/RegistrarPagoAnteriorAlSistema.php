<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Contracts\PagosAnterioresPermitidos;
use App\Modules\Pagos\Contracts\RegistroDePagoAnterior;
use App\Modules\Pagos\Data\PagoAnteriorAlSistema;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Events\PagoValidado;
use App\Modules\Pagos\Support\AltaDePago;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;

// Loads the cycle in progress: the money was received before the system, so the staff member records it already validated (pending decision 153).
final readonly class RegistrarPagoAnteriorAlSistema implements RegistroDePagoAnterior
{
    public function __construct(
        private AltaDePago $altaDePago,
        private PagosAnterioresPermitidos $permitidos,
    ) {}

    public function __invoke(PagoAnteriorAlSistema $pago, User $actor): int
    {
        // A validated payment is money received: only staff who register payments may record one.
        throw_unless($actor->can(PermisoPago::Registrar->value), AuthorizationException::class);

        if (! ($this->permitidos)()) {
            throw new ReglaDeNegocioIncumplida('El registro de pagos anteriores al sistema está desactivado en la configuración de pagos.');
        }

        $registrado = $this->altaDePago->registrar($pago->registro, $pago->recibo, [
            'estado' => EstadoPago::Validado,
            'registrado_por' => $actor->id,
            'validado_por' => $actor->id,
            'validado_en' => now(),
            'exige_boleta_original' => false,
            'anterior_al_sistema' => true,
            'motivo_anterior' => $this->motivo($pago->motivo),
        ]);

        PagoValidado::dispatch($registrado->id);

        return $registrado->id;
    }

    private function motivo(string $motivo): string
    {
        $motivo = trim((string) preg_replace('/\s+/u', ' ', $motivo));

        if ($motivo === '') {
            throw new ReglaDeNegocioIncumplida('Escribe el motivo del pago anterior al sistema.');
        }

        return mb_strlen($motivo) <= 500 ? $motivo : throw new ReglaDeNegocioIncumplida('El motivo del pago anterior al sistema no puede pasar de 500 caracteres.');
    }
}
