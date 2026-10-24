<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Matricula\Data\ResultadoDeClasificacion;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Matricula\Support\MovimientoDeMatricula;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

final readonly class RegistrarResultadoDeClasificacion
{
    public function __construct(private CatalogoDeIdiomas $catalogoDeIdiomas) {}

    public function __invoke(int $solicitudId, ResultadoDeClasificacion $resultado, User $evaluador): SolicitudClasificacion
    {
        if (preg_match('/^(100(\.0{1,2})?|\d{1,2}(\.\d{1,2})?)$/', $resultado->puntaje) !== 1) {
            throw new ReglaDeNegocioIncumplida('El puntaje va de 0 a 100, con hasta dos decimales.');
        }

        // Person first, like enrollment, so the enabled cycle never changes under an enrollment in progress.
        return Transaccion::ejecutar(function () use ($solicitudId, $resultado, $evaluador): SolicitudClasificacion {
            MovimientoDeMatricula::bloquearPersona((int) SolicitudClasificacion::query()->whereKey($solicitudId)->value('persona_id'));
            $solicitud = SolicitudClasificacion::query()->whereKey($solicitudId)->lockForUpdate()->firstOrFail();

            $this->asegurarEvaluable($solicitud, $resultado);

            $solicitud->update([
                'estado' => EstadoSolicitudClasificacion::Evaluado,
                'fecha_examen' => $resultado->fechaExamen->toDateString(),
                'puntaje' => $resultado->puntaje,
                'ciclo_asignado_id' => $resultado->cicloId,
                'observaciones' => filled($resultado->observaciones) ? trim($resultado->observaciones) : null,
                'evaluado_por' => $evaluador->id,
                'evaluado_en' => now(),
            ]);

            Habilitacion::query()->updateOrCreate(
                ['persona_id' => $solicitud->persona_id, 'idioma_id' => $solicitud->idioma_id],
                ['ciclo_id' => $resultado->cicloId, 'origen' => OrigenHabilitacion::Clasificacion, 'actualizado_por' => $evaluador->id],
            );

            return $solicitud;
        });
    }

    private function asegurarEvaluable(SolicitudClasificacion $solicitud, ResultadoDeClasificacion $resultado): void
    {
        if ($solicitud->estado !== EstadoSolicitudClasificacion::PendienteEvaluacion) {
            throw new ReglaDeNegocioIncumplida("La solicitud ya está {$solicitud->estado->etiqueta()}.");
        }

        $idioma = collect(($this->catalogoDeIdiomas)())->firstOrFail(fn (IdiomaDelCatalogo $idioma): bool => $idioma->id === $solicitud->idioma_id);

        if (! array_key_exists($resultado->cicloId, $idioma->ciclos)) {
            throw new ReglaDeNegocioIncumplida("El ciclo asignado no pertenece a {$idioma->nombre}.");
        }

        // Calendar dates in Lima, compared as strings so no time zone shifts a day.
        $desde = $solicitud->created_at->setTimezone('America/Lima');
        $hasta = min(CarbonImmutable::today('America/Lima')->toDateString(), $solicitud->vence_en->toDateString());
        $fecha = $resultado->fechaExamen->toDateString();

        if ($fecha < $desde->toDateString() || $fecha > $hasta) {
            throw new ReglaDeNegocioIncumplida("La fecha del examen debe estar entre el {$desde->format('d/m/Y')} y el ".CarbonImmutable::parse($hasta)->format('d/m/Y').'.');
        }
    }
}
