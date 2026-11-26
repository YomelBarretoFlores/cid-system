<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Certificacion\Contracts\DocumentoParaTramite;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Matricula\Contracts\MovimientoPorTramite;
use App\Modules\Tramites\Data\DatosDeResolucion;
use App\Modules\Tramites\Data\ResultadoDeAccionFinal;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\CampoFut;
use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\GestionDelPersonal;
use App\Modules\Tramites\Support\PresentacionDelFut;
use App\Modules\Tramites\Support\ReclasificacionDelTramite;
use App\Modules\Tramites\Support\RegistroDeMovimiento;
use App\Modules\Tramites\Support\SuficienciaDelTramite;
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
        private ReclasificacionDelTramite $reclasificacion,
        private SuficienciaDelTramite $suficiencia,
    ) {}

    public function __invoke(int $tramiteId, User $usuario, ?int $grupoDestinoId = null, DatosDeResolucion $resolucion = new DatosDeResolucion): Tramite
    {
        return Transaccion::ejecutar(function () use ($tramiteId, $usuario, $grupoDestinoId, $resolucion): Tramite {
            $tramite = GestionDelPersonal::bloquear($tramiteId, $usuario, [EstadoTramite::EnProceso]);

            throw_unless(
                $tramite->etapa_actual + 1 === count($tramite->etapas) - 1 && $tramite->condicionDeLaSiguiente() === CondicionDeEtapa::AccionDelPersonal,
                ReglaDeNegocioIncumplida::class,
                'El trámite aún no llega a su penúltima etapa.',
            );

            $resultado = $this->ejecutarAccionFinal($tramite, $usuario, $grupoDestinoId, $resolucion);

            $tramite->update([
                'etapa_actual' => count($tramite->etapas) - 1,
                'estado' => EstadoTramite::Finalizado,
                'documento_id' => $resultado->documentoId,
                'resultado' => $resultado->resultado,
                'finalizado_en' => CarbonImmutable::now(),
            ]);
            RegistroDeMovimiento::registrar($tramite, $usuario->id, $resultado->comentario);

            return $tramite;
        });
    }

    private function ejecutarAccionFinal(Tramite $tramite, User $usuario, ?int $grupoDestinoId, DatosDeResolucion $resolucion): ResultadoDeAccionFinal
    {
        return match ($tramite->accion_final) {
            AccionFinal::ConstanciaMatricula => $this->documento(TipoDocumento::ConstanciaMatricula, $tramite, $usuario),
            AccionFinal::ConstanciaNotas => $this->documento(TipoDocumento::ConstanciaNotas, $tramite, $usuario),
            AccionFinal::CertificadoNivel => $this->documento(TipoDocumento::CertificadoNivel, $tramite, $usuario),
            AccionFinal::Acreditacion => $this->documento(TipoDocumento::ConstanciaAcreditacion, $tramite, $usuario),
            AccionFinal::Traslado, AccionFinal::Retiro => $this->mover($tramite, $usuario, $grupoDestinoId),
            AccionFinal::Suficiencia => $this->suficiencia->resolver($tramite, $usuario, $resolucion),
            AccionFinal::Reclasificacion => $this->reclasificacion->resolver($tramite, $usuario, $resolucion),
            AccionFinal::Resolucion => $this->resolver($resolucion),
            AccionFinal::Ninguna => new ResultadoDeAccionFinal,
        };
    }

    private function documento(TipoDocumento $tipo, Tramite $tramite, User $usuario): ResultadoDeAccionFinal
    {
        $datos = $tipo === TipoDocumento::ConstanciaAcreditacion
            ? [...$tramite->datos, 'mencion_completa' => PresentacionDelFut::valor(CampoFut::Mencion, $tramite->datos) ?? '']
            : $tramite->datos;

        return new ResultadoDeAccionFinal(documentoId: ($this->documentoParaTramite)($tipo, $tramite->persona_id, $datos, $usuario)->id);
    }

    private function mover(Tramite $tramite, User $usuario, ?int $grupoDestinoId): ResultadoDeAccionFinal
    {
        // A null destination means a withdrawal, so only a transfer passes the chosen group on.
        $destino = $tramite->accion_final === AccionFinal::Traslado
            ? ($grupoDestinoId ?? throw new ReglaDeNegocioIncumplida('Elige el grupo de destino del traslado.'))
            : null;
        $motivo = mb_substr("Trámite {$tramite->codigo}: ".($tramite->datos['motivo'] ?? ''), 0, 500);
        ($this->movimientoPorTramite)((int) ($tramite->datos['matricula_id'] ?? 0), $destino, $motivo, $usuario);

        return new ResultadoDeAccionFinal;
    }

    private function resolver(DatosDeResolucion $resolucion): ResultadoDeAccionFinal
    {
        $comentario = trim((string) $resolucion->comentario);

        throw_unless(mb_strlen($comentario) >= 5 && mb_strlen($comentario) <= 500, ReglaDeNegocioIncumplida::class, 'Escribe la respuesta al estudiante, entre 5 y 500 caracteres.');

        return new ResultadoDeAccionFinal(resultado: ['comentario' => $comentario], comentario: $comentario);
    }
}
