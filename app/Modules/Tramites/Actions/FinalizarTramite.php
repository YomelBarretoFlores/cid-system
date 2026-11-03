<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Certificacion\Contracts\DocumentoParaTramite;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Matricula\Contracts\MovimientoPorTramite;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\GestionDelPersonal;
use App\Modules\Tramites\Support\RegistroDeMovimiento;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

// Reaching the last stage runs the final action as the staff member; if it is refused, nothing of the trámite changes.
final readonly class FinalizarTramite
{
    public function __construct(
        private DocumentoParaTramite $documentoParaTramite,
        private MovimientoPorTramite $movimientoPorTramite,
    ) {}

    public function __invoke(int $tramiteId, User $usuario, ?int $grupoDestinoId = null): Tramite
    {
        return Transaccion::ejecutar(function () use ($tramiteId, $usuario, $grupoDestinoId): Tramite {
            $tramite = GestionDelPersonal::bloquear($tramiteId, $usuario, [EstadoTramite::EnProceso]);

            throw_unless(
                $tramite->etapa_actual + 1 === count($tramite->etapas) - 1 && $tramite->condicionDeLaSiguiente() === CondicionDeEtapa::AccionDelPersonal,
                ReglaDeNegocioIncumplida::class,
                'El trámite aún no llega a su penúltima etapa.',
            );

            $documentoId = $this->ejecutarAccionFinal($tramite, $usuario, $grupoDestinoId);

            $tramite->update([
                'etapa_actual' => count($tramite->etapas) - 1,
                'estado' => EstadoTramite::Finalizado,
                'documento_id' => $documentoId,
                'finalizado_en' => CarbonImmutable::now(),
            ]);
            RegistroDeMovimiento::registrar($tramite, $usuario->id);

            return $tramite;
        });
    }

    private function ejecutarAccionFinal(Tramite $tramite, User $usuario, ?int $grupoDestinoId): ?int
    {
        $tipoDocumento = match ($tramite->accion_final) {
            AccionFinal::ConstanciaMatricula => TipoDocumento::ConstanciaMatricula,
            AccionFinal::ConstanciaNotas => TipoDocumento::ConstanciaNotas,
            AccionFinal::CertificadoNivel => TipoDocumento::CertificadoNivel,
            default => null,
        };

        if ($tipoDocumento !== null) {
            return ($this->documentoParaTramite)($tipoDocumento, $tramite->persona_id, $tramite->datos, $usuario)->id;
        }

        if (! in_array($tramite->accion_final, [AccionFinal::Traslado, AccionFinal::Retiro], true)) {
            return null;
        }

        // A null destination means a withdrawal, so only a transfer passes the chosen group on.
        $destino = $tramite->accion_final === AccionFinal::Traslado
            ? ($grupoDestinoId ?? throw new ReglaDeNegocioIncumplida('Elige el grupo de destino del traslado.'))
            : null;
        $motivo = mb_substr("Trámite {$tramite->codigo}: ".($tramite->datos['motivo'] ?? ''), 0, 500);
        ($this->movimientoPorTramite)((int) ($tramite->datos['matricula_id'] ?? 0), $destino, $motivo, $usuario);

        return null;
    }
}
