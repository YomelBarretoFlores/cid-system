<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Contracts\BloqueoDeGrupo;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\DatosDelCurso;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Certificacion\Contracts\DenominacionDelAnio;
use App\Modules\Certificacion\Data\ActaDelGrupo;
use App\Modules\Certificacion\Data\FilaDelActa;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Enums\GrupoDeFirma;
use App\Modules\Certificacion\Events\ActaCerrada;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\Rectificacion;
use App\Modules\Certificacion\Support\ActaPrevia;
use App\Modules\Certificacion\Support\DocenteDelActa;
use App\Modules\Certificacion\Support\FirmasDelDocumento;
use App\Modules\Certificacion\Support\ResultadosDelActa;
use App\Modules\Certificacion\Support\RevisionDeDocumentos;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Ui\Formato;
use Carbon\CarbonImmutable;

// Validating the acta closes it: the first time from the pre-acta inside the grade calendar, later only after an authorized rectification.
final readonly class CerrarActa
{
    private const array ESTADOS_CON_ACTA = [EstadoGrupo::Abierto, EstadoGrupo::Cerrado, EstadoGrupo::Finalizado];

    public function __construct(
        private BloqueoDeGrupo $bloqueoDeGrupo,
        private ActaPrevia $actaPrevia,
        private DocenteDelActa $docenteDelActa,
        private DenominacionDelAnio $denominacionDelAnio,
    ) {}

    public function __invoke(int $grupoId, int $docenteId, User $usuario): void
    {
        Transaccion::ejecutar(function () use ($grupoId, $docenteId, $usuario): void {
            // Same group lock as the pre-acta, so no partial grade changes while the acta copies them.
            $grupo = ($this->bloqueoDeGrupo)($grupoId);
            $this->docenteDelActa->asegurar($grupoId, $grupo->codigo, $docenteId);
            $acta = Acta::query()->where('grupo_id', $grupoId)->lockForUpdate()->first();

            if ($acta === null) {
                $this->cerrarPorPrimeraVez($grupoId, $usuario);

                return;
            }

            if ($acta->estado === EstadoActa::Cerrada) {
                throw new ReglaDeNegocioIncumplida("El acta final del grupo {$grupo->codigo} ya está cerrada.");
            }

            $this->validar($acta, $usuario);
            $this->cerrarRectificacion($acta);
        });
    }

    private function cerrarRectificacion(Acta $acta): void
    {
        $rectificacion = Rectificacion::query()->where('acta_id', $acta->id)->whereNull('cerrada_en')->lockForUpdate()->first();
        RevisionDeDocumentos::marcar($acta, ResultadosDelActa::personasConCambios($acta, $rectificacion?->notas_previas));
        $rectificacion?->update(['cerrada_en' => now()]);
    }

    private function cerrarPorPrimeraVez(int $grupoId, User $usuario): void
    {
        $curso = $this->actaPrevia->curso($grupoId);
        $this->asegurarCalendario($curso);
        $previa = $this->actaPrevia->armar($grupoId);

        if ($previa->filas === []) {
            throw new ReglaDeNegocioIncumplida("El grupo {$curso->codigo} no tiene estudiantes activos para el acta final.");
        }

        $acta = Acta::query()->create($this->datosDelActa($previa, $curso));
        $acta->notas()->createMany(array_map(fn (FilaDelActa $fila): array => [
            'matricula_id' => $fila->matriculaId, 'persona_id' => $fila->personaId, 'estudiante_nombre' => $fila->estudiante,
            'estudiante_documento' => $fila->documento, 'notas' => $fila->notas, 'promedio' => $fila->promedio, 'estado_final' => $fila->estadoFinal,
        ], $previa->filas));
        $this->validar($acta, $usuario);
    }

    private function asegurarCalendario(DatosDelCurso $curso): void
    {
        if (! in_array($curso->estado, self::ESTADOS_CON_ACTA, true)) {
            throw new ReglaDeNegocioIncumplida("El grupo {$curso->codigo} está {$curso->estado->etiqueta()}; su acta final no se puede validar.");
        }

        if (! $curso->actasInicio instanceof CarbonImmutable || ! $curso->actasFin instanceof CarbonImmutable) {
            throw new ReglaDeNegocioIncumplida("El periodo {$curso->periodo} no tiene fechas para actas finales; pide a coordinación que las registre.");
        }

        if (! $curso->admiteActasEl(CarbonImmutable::now())) {
            $desde = Formato::fechaCorta($curso->actasInicio);
            $hasta = Formato::fechaCorta($curso->actasFin);

            throw new ReglaDeNegocioIncumplida("El acta final del grupo {$curso->codigo} se valida del {$desde} al {$hasta}.");
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function datosDelActa(ActaDelGrupo $previa, DatosDelCurso $curso): array
    {
        return [
            'grupo_id' => $previa->grupoId, 'periodo_id' => $curso->periodoId, 'estado' => EstadoActa::Abierta,
            'grupo_codigo' => $previa->grupoCodigo, 'idioma' => $previa->idioma, 'nivel' => $previa->nivel,
            'ciclo_numero' => $previa->cicloNumero, 'ciclo_nombre' => $previa->cicloNombre, 'horas_lectivas' => $curso->horasLectivas,
            'ciclo_numero_hasta' => $previa->cicloNumeroHasta, 'ciclo_nombre_hasta' => $previa->cicloNombreHasta, 'horas_lectivas_hasta' => $curso->horasLectivasHasta,
            'periodo' => $previa->periodo, 'docente_id' => $previa->docenteId, 'docente_nombre' => $previa->docente,
            'esquema_id' => $previa->esquemaId, 'esquema_nombre' => $previa->esquema,
            'nota_maxima' => $previa->notaMaxima, 'nota_minima_aprobatoria' => $previa->notaMinimaAprobatoria,
            'componentes' => array_map(fn (ComponenteDeEvaluacion $componente): array => $componente->aArreglo(), $previa->componentes),
        ];
    }

    private function validar(Acta $acta, User $usuario): void
    {
        $validada = now();
        $enLima = $validada->setTimezone('America/Lima');

        // The signers and the year's denomination in force are frozen like a document's, so editing them never rewrites a closed acta.
        $acta->update([
            'estado' => EstadoActa::Cerrada, 'validada_por' => $usuario->id, 'validada_en' => $validada,
            'firmas' => FirmasDelDocumento::de(GrupoDeFirma::Actas, $enLima), 'denominacion' => ($this->denominacionDelAnio)((int) $enLima->format('Y')),
        ]);

        $aprobadas = $acta->notas()->where('estado_final', EstadoFinal::Aprobado)->orderBy('matricula_id')->pluck('matricula_id')->all();
        ActaCerrada::dispatch($acta->grupo_id, array_values(array_map(intval(...), $aprobadas)));
    }
}
