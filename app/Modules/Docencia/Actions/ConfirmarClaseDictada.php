<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\BloqueoDeGrupo;
use App\Modules\Docencia\Enums\ConfirmacionDeClase;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Models\Asistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Support\ClasesDelHorario;
use App\Modules\Docencia\Support\DelegadoDelGrupo;
use App\Modules\Docencia\Support\VentanasDeLlamado;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class ConfirmarClaseDictada
{
    private const int OBSERVACION_MINIMA = 5;

    private const int OBSERVACION_MAXIMA = 500;

    public function __construct(
        private BloqueoDeGrupo $bloqueoDeGrupo,
        private DelegadoDelGrupo $delegadoDelGrupo,
    ) {}

    public function __invoke(int $claseId, int $personaId, bool $dictada, ?string $observacion): ClaseDictada
    {
        $observacion = $dictada ? null : $this->observacionValida($observacion);
        $grupoId = ClaseDictada::query()->whereKey($claseId)->value('grupo_id') ?? throw new ReglaDeNegocioIncumplida('La clase no existe.');

        return Transaccion::ejecutar(function () use ($claseId, $grupoId, $personaId, $dictada, $observacion): ClaseDictada {
            // Same lock order as the teacher's roll calls: the group first, then the class.
            $grupo = ($this->bloqueoDeGrupo)((int) $grupoId);
            $clase = ClaseDictada::query()->whereKey($claseId)->lockForUpdate()->firstOrFail();
            $delegado = ($this->delegadoDelGrupo)($clase->grupo_id);

            if ($delegado?->personaId !== $personaId) {
                throw new ReglaDeNegocioIncumplida("Solo el delegado del grupo {$grupo->codigo} puede confirmar sus clases.");
            }

            $this->asegurarPorConfirmar($clase);
            $this->asegurarPresenteAlCierre($clase, $delegado->matriculaId);

            $clase->fill([
                'confirmacion' => $dictada ? ConfirmacionDeClase::Confirmada : ConfirmacionDeClase::Observada,
                'confirmada_por_matricula_id' => $delegado->matriculaId,
                'confirmada_en' => now(),
                'observacion' => $observacion,
            ])->save();

            return $clase;
        });
    }

    private function observacionValida(?string $observacion): string
    {
        $texto = trim((string) $observacion);

        if (mb_strlen($texto) < self::OBSERVACION_MINIMA) {
            throw new ReglaDeNegocioIncumplida('Explica en al menos '.self::OBSERVACION_MINIMA.' caracteres por qué observas la clase.');
        }

        if (mb_strlen($texto) > self::OBSERVACION_MAXIMA) {
            throw new ReglaDeNegocioIncumplida('La observación admite hasta '.self::OBSERVACION_MAXIMA.' caracteres.');
        }

        return $texto;
    }

    private function asegurarPorConfirmar(ClaseDictada $clase): void
    {
        if (in_array($clase->confirmacion, [ConfirmacionDeClase::Confirmada, ConfirmacionDeClase::Observada], true)) {
            $resuelta = $clase->confirmacion === ConfirmacionDeClase::Confirmada ? 'confirmada' : 'observada';
            $momento = $clase->confirmada_en?->setTimezone(ClasesDelHorario::ZONA);

            throw new ReglaDeNegocioIncumplida("Esta clase ya fue {$resuelta} el {$momento?->format('d/m/Y')} a las {$momento?->format('H:i')}.");
        }

        if ($clase->registro_posterior) {
            throw new ReglaDeNegocioIncumplida('Esta clase es un registro posterior; no necesita la confirmación del delegado.');
        }

        if ($clase->confirmacion !== ConfirmacionDeClase::Pendiente || $clase->llamado_cierre_en === null) {
            throw new ReglaDeNegocioIncumplida('El docente aún no toma el llamado de cierre de esta clase.');
        }

        $vence = VentanasDeLlamado::deLaClase($clase)->venceConfirmacion();

        if (ClasesDelHorario::ahora()->gt($vence)) {
            throw new ReglaDeNegocioIncumplida("El plazo para confirmar la clase del {$clase->fecha->format('d/m/Y')} venció a las {$vence->format('H:i')}.");
        }
    }

    private function asegurarPresenteAlCierre(ClaseDictada $clase, int $matriculaId): void
    {
        $asistencia = Asistencia::query()->where(['clase_id' => $clase->id, 'matricula_id' => $matriculaId])->first();
        $estado = $asistencia === null ? EstadoAsistencia::Falta : EstadoAsistencia::efectivo($asistencia->estado, $asistencia->estado_cierre);

        if ($estado === EstadoAsistencia::Falta) {
            throw new ReglaDeNegocioIncumplida('Solo puedes confirmar una clase en la que figuras presente en el llamado de cierre.');
        }
    }
}
