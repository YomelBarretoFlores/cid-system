<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\ConsultaGruposDelCiclo;
use App\Modules\Academico\Data\GrupoData;
use App\Modules\Matricula\Data\ResultadoDeClasificacion;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Modules\Matricula\Events\ClasificacionRegistrada;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Matricula\Support\MovimientoDeMatricula;
use App\Modules\Matricula\Support\ValidacionDeClasificacion;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Fixes a recorded placement result; the enabled cycle follows only while it still comes from this placement and was not used to enroll.
final readonly class CorregirResultadoDeClasificacion
{
    private const string AJUSTE = '«Ajustar ciclo habilitado» en Ciclos habilitados';

    public function __construct(
        private ValidacionDeClasificacion $validacion,
        private ConsultaGruposDelCiclo $gruposDelCiclo,
    ) {}

    public function __invoke(int $solicitudId, ResultadoDeClasificacion $resultado, string $motivo, User $usuario): SolicitudClasificacion
    {
        throw_unless($usuario->can(PermisoClasificacion::Evaluar->value), ReglaDeNegocioIncumplida::class, 'No tienes permiso para corregir resultados de clasificación.');

        $motivo = trim($motivo);
        throw_unless(mb_strlen($motivo) >= 5 && mb_strlen($motivo) <= 500, ReglaDeNegocioIncumplida::class, 'Indica el motivo de la corrección, entre 5 y 500 caracteres.');
        ValidacionDeClasificacion::puntaje($resultado->puntaje);

        return Transaccion::ejecutar(function () use ($solicitudId, $resultado, $motivo, $usuario): SolicitudClasificacion {
            MovimientoDeMatricula::bloquearPersona((int) SolicitudClasificacion::query()->whereKey($solicitudId)->value('persona_id'));
            $solicitud = SolicitudClasificacion::query()->whereKey($solicitudId)->lockForUpdate()->firstOrFail();

            throw_unless($solicitud->estado === EstadoSolicitudClasificacion::Evaluado, ReglaDeNegocioIncumplida::class, 'Solo se corrige el resultado de una solicitud evaluada.');
            $this->validacion->cicloYFecha($solicitud, $resultado);

            if ($resultado->cicloId !== $solicitud->ciclo_asignado_id) {
                $this->moverHabilitacion($solicitud, $resultado->cicloId, $usuario);
                ClasificacionRegistrada::dispatch($solicitud->id, $solicitud->persona_id, $resultado->cicloId, $resultado->fechaExamen->toDateString(), $usuario->id);
            }

            $solicitud->update([
                'fecha_examen' => $resultado->fechaExamen->toDateString(),
                'puntaje' => $resultado->puntaje,
                'ciclo_asignado_id' => $resultado->cicloId,
                'observaciones' => filled($resultado->observaciones) ? trim($resultado->observaciones) : null,
                'corregido_por' => $usuario->id,
                'corregido_en' => now(),
                'motivo_correccion' => $motivo,
            ]);

            return $solicitud;
        });
    }

    private function moverHabilitacion(SolicitudClasificacion $solicitud, int $cicloId, User $usuario): void
    {
        $habilitacion = Habilitacion::query()->where(['persona_id' => $solicitud->persona_id, 'idioma_id' => $solicitud->idioma_id])->first();

        throw_unless($this->vieneDeEstaClasificacion($solicitud, $habilitacion), ReglaDeNegocioIncumplida::class, 'El ciclo habilitado ya no proviene de esta clasificación: use '.self::AJUSTE.'.');

        $grupos = array_map(fn (GrupoData $grupo): int => $grupo->id, ($this->gruposDelCiclo)((int) $solicitud->ciclo_asignado_id));
        $seMatriculo = Matricula::query()->where('persona_id', $solicitud->persona_id)->whereIn('grupo_id', $grupos)->exists();

        throw_if($seMatriculo, ReglaDeNegocioIncumplida::class, 'El estudiante ya se matriculó en el ciclo asignado: su ciclo se cambia con '.self::AJUSTE.'.');

        $habilitacion?->update(['ciclo_id' => $cicloId, 'actualizado_por' => $usuario->id]);
    }

    private function vieneDeEstaClasificacion(SolicitudClasificacion $solicitud, ?Habilitacion $habilitacion): bool
    {
        $posterior = SolicitudClasificacion::query()
            ->where(['persona_id' => $solicitud->persona_id, 'idioma_id' => $solicitud->idioma_id, 'estado' => EstadoSolicitudClasificacion::Evaluado])
            ->where('evaluado_en', '>', $solicitud->evaluado_en)
            ->exists();

        return ! $posterior
            && $habilitacion?->origen === OrigenHabilitacion::Clasificacion
            && $habilitacion->ciclo_id === $solicitud->ciclo_asignado_id;
    }
}
