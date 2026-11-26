<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Pagos\Contracts\PagosValidadosDePersona;
use App\Modules\Pagos\Contracts\VerificacionDePago;
use App\Modules\Pagos\Data\PagoValidado;
use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;

// Callers hold the person's lock; the payment row lock comes second, as in every other consumer of Pagos.
final readonly class AplicacionDePago
{
    public function __construct(
        private PagosValidadosDePersona $pagosValidados,
        private VerificacionDePago $verificacionDePago,
    ) {}

    /** The person's oldest validated payment the trámite accepts that no trámite uses yet. */
    public function pagoLibre(Tramite $tramite): ?int
    {
        if ($tramite->tipo_concepto === null) {
            return null;
        }

        $aceptados = array_filter(
            ($this->pagosValidados)($tramite->persona_id, $tramite->tipo_concepto),
            fn (PagoValidado $pago): bool => self::admite($tramite, $pago->conceptoId, $pago->nivel),
        );
        $ids = array_values(array_map(fn (PagoValidado $pago): int => $pago->id, $aceptados));
        $usados = Tramite::query()->whereIn('pago_id', $ids)->pluck('pago_id')->all();
        $libres = array_values(array_diff($ids, $usados));

        // The contract lists the newest payment first.
        return $libres === [] ? null : end($libres);
    }

    /**
     * A trámite whose type names a concept takes only a payment of that concept, and of its level when the fee was per level.
     * The fee's program is not checked: the student's program for the language is not known here.
     */
    public static function admite(Tramite $tramite, ?int $conceptoId, ?NivelCurricular $nivel): bool
    {
        if ($tramite->concepto_id === null) {
            return true;
        }

        return $conceptoId === $tramite->concepto_id && (! $nivel instanceof NivelCurricular || $nivel->value === ($tramite->datos['nivel'] ?? null));
    }

    // The payment may be voided or taken between the lookup and its lock; the trámite then simply keeps waiting for one.
    public function aplicarSiSigueLibre(Tramite $tramite, int $pagoId): bool
    {
        try {
            $this->aplicar($tramite, $pagoId);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);

            return false;
        }

        return true;
    }

    public function aplicar(Tramite $tramite, int $pagoId): void
    {
        $tipo = $tramite->tipo_concepto ?? throw new ReglaDeNegocioIncumplida('Este trámite no tiene tasa.');
        $pago = ($this->verificacionDePago)($pagoId, $tramite->persona_id, $tipo);

        throw_unless(self::admite($tramite, $pago->conceptoId, $pago->nivel), ReglaDeNegocioIncumplida::class, 'El pago no corresponde al concepto o al nivel de este trámite.');

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
