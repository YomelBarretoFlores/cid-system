<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Matricula\Data\ResultadoDeClasificacion;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Modules\Matricula\Events\ClasificacionRegistrada;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Matricula\Support\MovimientoDeMatricula;
use App\Modules\Matricula\Support\ValidacionDeClasificacion;
use App\Modules\Pagos\Contracts\VerificacionDePago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class RegistrarResultadoDeClasificacion
{
    public function __construct(
        private ValidacionDeClasificacion $validacion,
        private VerificacionDePago $verificacionDePago,
    ) {}

    public function __invoke(int $solicitudId, ResultadoDeClasificacion $resultado, User $evaluador): SolicitudClasificacion
    {
        throw_unless($evaluador->can(PermisoClasificacion::Evaluar->value), ReglaDeNegocioIncumplida::class, 'No tienes permiso para registrar resultados de clasificación.');
        $puntajeMaximo = $this->validacion->puntaje($resultado->puntaje);

        // Person first, like enrollment, so the enabled cycle never changes under an enrollment in progress.
        return Transaccion::ejecutar(function () use ($solicitudId, $resultado, $evaluador, $puntajeMaximo): SolicitudClasificacion {
            MovimientoDeMatricula::bloquearPersona((int) SolicitudClasificacion::query()->whereKey($solicitudId)->value('persona_id'));
            $solicitud = SolicitudClasificacion::query()->whereKey($solicitudId)->lockForUpdate()->firstOrFail();

            if ($solicitud->estado !== EstadoSolicitudClasificacion::PendienteEvaluacion) {
                throw new ReglaDeNegocioIncumplida("La solicitud ya está {$solicitud->estado->etiqueta()}.");
            }

            $this->asegurarPagoValidado($solicitud);
            $this->validacion->cicloYFecha($solicitud, $resultado);

            $solicitud->update([
                'estado' => EstadoSolicitudClasificacion::Evaluado,
                'fecha_examen' => $resultado->fechaExamen->toDateString(),
                'puntaje' => $resultado->puntaje,
                'puntaje_maximo' => $puntajeMaximo,
                'ciclo_asignado_id' => $resultado->cicloId,
                'observaciones' => filled($resultado->observaciones) ? trim($resultado->observaciones) : null,
                'evaluado_por' => $evaluador->id,
                'evaluado_en' => now(),
            ]);

            Habilitacion::query()->updateOrCreate(
                ['persona_id' => $solicitud->persona_id, 'idioma_id' => $solicitud->idioma_id],
                ['ciclo_id' => $resultado->cicloId, 'origen' => OrigenHabilitacion::Clasificacion, 'motivo' => null, 'actualizado_por' => $evaluador->id],
            );

            ClasificacionRegistrada::dispatch($solicitud->id, $solicitud->persona_id, $resultado->cicloId, $resultado->fechaExamen->toDateString(), $evaluador->id);

            return $solicitud;
        });
    }

    // The voided-payment listener runs after Pagos commits, so the payment is checked (and locked) here as well.
    private function asegurarPagoValidado(SolicitudClasificacion $solicitud): void
    {
        if ($solicitud->pago_id === null) {
            return;
        }

        try {
            ($this->verificacionDePago)($solicitud->pago_id, $solicitud->persona_id, TipoConcepto::ExamenClasificacion);
        } catch (ReglaDeNegocioIncumplida) {
            throw new ReglaDeNegocioIncumplida('El pago del examen ya no está validado: no se puede registrar el resultado.');
        }
    }
}
