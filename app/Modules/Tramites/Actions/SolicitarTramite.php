<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Certificacion\Contracts\IdiomasConNotasCerradas;
use App\Modules\Certificacion\Contracts\NivelesPorCertificar;
use App\Modules\Certificacion\Data\NivelPorCertificar;
use App\Modules\Matricula\Contracts\MatriculasActivasDePersona;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\AccionFinal;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Events\TramiteActualizado;
use App\Modules\Tramites\Models\Etapa;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\AplicacionDePago;
use App\Modules\Tramites\Support\RegistroDeMovimiento;
use App\Modules\Tramites\Support\ReglasDeEtapas;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;

final readonly class SolicitarTramite
{
    private const int MAXIMO_TEXTO = 500;

    public function __construct(
        private MatriculasActivasDePersona $matriculasActivas,
        private IdiomasConNotasCerradas $idiomasConNotas,
        private NivelesPorCertificar $nivelesPorCertificar,
        private AplicacionDePago $aplicacionDePago,
    ) {}

    public function __invoke(int $personaId, int $tipoTramiteId, DatosDeSolicitud $datos): Tramite
    {
        $tipo = TipoTramite::query()->with('etapas')->whereKey($tipoTramiteId)->where('activo', true)->first()
            ?? throw new ReglaDeNegocioIncumplida('Este trámite no está disponible.');
        $etapas = $tipo->etapas->map(fn (Etapa $etapa): array => ['nombre' => $etapa->nombre, 'avance' => $etapa->avance, 'condicion' => $etapa->condicion])->all();
        ReglasDeEtapas::validar(array_values($etapas), $tipo->cobraTasa());

        return Transaccion::ejecutar(function () use ($personaId, $tipo, $etapas, $datos): Tramite {
            RegistroDeMovimiento::bloquearPersona($personaId);
            $guardados = $this->datos($tipo->accion_final, $personaId, $datos);
            $this->asegurarUnoEnCurso($personaId, $tipo, $guardados);

            $tramite = Tramite::query()->create([
                'persona_id' => $personaId,
                'tipo_tramite_id' => $tipo->id,
                'tipo_nombre' => $tipo->nombre,
                'accion_final' => $tipo->accion_final,
                'tipo_concepto' => $tipo->tipo_concepto,
                'etapas' => array_map(fn (array $etapa): array => [...$etapa, 'condicion' => $etapa['condicion']->value], array_values($etapas)),
                'estado' => $tipo->cobraTasa() ? EstadoTramite::PendientePago : EstadoTramite::EnProceso,
                'datos' => $guardados,
            ])->refresh();

            $pagoLibre = $tipo->tipo_concepto === null ? null : $this->aplicacionDePago->pagoLibre($personaId, $tipo->tipo_concepto);
            $solicitud = RegistroDeMovimiento::registrar($tramite, notificar: $pagoLibre === null);

            if ($pagoLibre !== null && ! $this->aplicarSiSigueLibre($tramite, $pagoLibre)) {
                TramiteActualizado::dispatch($tramite->id, $solicitud->id);
            }

            return $tramite;
        });
    }

    // The payment may be voided between the lookup and its lock; the request then simply waits for a payment.
    private function aplicarSiSigueLibre(Tramite $tramite, int $pagoId): bool
    {
        try {
            $this->aplicacionDePago->aplicar($tramite, $pagoId);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);

            return false;
        }

        return true;
    }

    /**
     * @return array<string, int|string>
     */
    private function datos(AccionFinal $accion, int $personaId, DatosDeSolicitud $datos): array
    {
        $guardados = [
            ...$accion->pideMatricula() ? $this->matricula($personaId, $datos->matriculaId) : [],
            ...$accion === AccionFinal::ConstanciaNotas ? $this->idiomaConNotas($personaId, $datos->idioma) : [],
            ...$accion === AccionFinal::CertificadoNivel ? $this->nivel($personaId, $datos->idioma, $datos->nivel) : [],
            ...$accion->pideMotivo() ? ['motivo' => $this->texto($datos->motivo, 'Indica el motivo, entre 5 y 500 caracteres.', obligatorio: true)] : [],
        ];
        $comentario = $this->texto($datos->comentario, 'El comentario admite hasta 500 caracteres.', obligatorio: false);

        return $comentario === '' ? $guardados : [...$guardados, 'comentario' => $comentario];
    }

    /**
     * @return array{matricula_id: int, matricula: string}
     */
    private function matricula(int $personaId, ?int $matriculaId): array
    {
        $activas = ($this->matriculasActivas)($personaId);

        throw_unless($matriculaId !== null && isset($activas[$matriculaId]), ReglaDeNegocioIncumplida::class, 'Elige una de tus matrículas activas.');

        return ['matricula_id' => $matriculaId, 'matricula' => $activas[$matriculaId]];
    }

    /**
     * @return array{idioma: string}
     */
    private function idiomaConNotas(int $personaId, ?string $idioma): array
    {
        throw_unless(in_array($idioma, ($this->idiomasConNotas)($personaId), true), ReglaDeNegocioIncumplida::class, 'Aún no tienes actas finales cerradas en ese idioma.');

        return ['idioma' => $idioma];
    }

    /**
     * @return array{idioma: string, nivel: string}
     */
    private function nivel(int $personaId, ?string $idioma, ?string $nivel): array
    {
        $disponible = collect(($this->nivelesPorCertificar)($personaId))
            ->first(fn (NivelPorCertificar $candidato): bool => $candidato->idioma === $idioma && $candidato->nivel->value === $nivel);

        throw_unless($disponible instanceof NivelPorCertificar, ReglaDeNegocioIncumplida::class, 'Aún no completas ese nivel o ya tienes su certificado vigente.');

        return ['idioma' => $disponible->idioma, 'nivel' => $disponible->nivel->value];
    }

    private function texto(?string $texto, string $mensaje, bool $obligatorio): string
    {
        $limpio = trim((string) $texto);
        $valido = mb_strlen($limpio) <= self::MAXIMO_TEXTO && (! $obligatorio || mb_strlen($limpio) >= 5);

        throw_unless($valido, ReglaDeNegocioIncumplida::class, $mensaje);

        return $limpio;
    }

    /**
     * @param  array<string, int|string>  $guardados
     */
    private function asegurarUnoEnCurso(int $personaId, TipoTramite $tipo, array $guardados): void
    {
        $asunto = array_intersect_key($guardados, array_flip(['matricula_id', 'idioma', 'nivel']));

        $enCurso = Tramite::query()
            ->where(['persona_id' => $personaId, 'tipo_tramite_id' => $tipo->id])
            ->where('estado', '!=', EstadoTramite::Finalizado->value)
            ->whereRaw('datos @> ?::jsonb', [json_encode((object) $asunto, JSON_THROW_ON_ERROR)])
            ->exists();

        throw_if($enCurso, ReglaDeNegocioIncumplida::class, "Ya tienes un trámite de {$tipo->nombre} en curso para lo mismo.");
    }
}
