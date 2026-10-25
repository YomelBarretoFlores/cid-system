<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Pagos\Contracts\PagosValidadosDePersona;
use App\Modules\Pagos\Contracts\VerificacionDePago;
use App\Modules\Pagos\Data\PagoValidado;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Callers hold the person's lock; the payment row lock comes second, as in every other consumer of Pagos.
final readonly class AplicacionDePago
{
    public function __construct(
        private PagosValidadosDePersona $pagosValidados,
        private VerificacionDePago $verificacionDePago,
    ) {}

    /** The person's oldest validated payment of the concept type that no trámite uses yet. */
    public function pagoLibre(int $personaId, TipoConcepto $tipo): ?int
    {
        $ids = array_map(fn (PagoValidado $pago): int => $pago->id, ($this->pagosValidados)($personaId, $tipo));
        $usados = Tramite::query()->whereIn('pago_id', $ids)->pluck('pago_id')->all();
        $libres = array_values(array_diff($ids, $usados));

        return $libres === [] ? null : end($libres);
    }

    public function aplicar(Tramite $tramite, int $pagoId): void
    {
        $tipo = $tramite->tipo_concepto ?? throw new ReglaDeNegocioIncumplida('Este trámite no tiene tasa.');
        ($this->verificacionDePago)($pagoId, $tramite->persona_id, $tipo);

        throw_if(Tramite::query()->where('pago_id', $pagoId)->exists(), ReglaDeNegocioIncumplida::class, 'El pago ya se usó en otro trámite.');

        $tramite->update([
            'pago_id' => $pagoId,
            'estado' => EstadoTramite::EnProceso,
            'observacion' => null,
            'etapa_actual' => (int) array_search(CondicionDeEtapa::PagoValidado->value, array_column($tramite->etapas, 'condicion'), true),
        ]);

        RegistroDeMovimiento::registrar($tramite);
    }
}
