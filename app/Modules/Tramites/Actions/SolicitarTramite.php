<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\CondicionDeEtapa;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Events\TramiteActualizado;
use App\Modules\Tramites\Models\Etapa;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Modules\Tramites\Support\AccesoAlTipo;
use App\Modules\Tramites\Support\AdjuntosDeSolicitud;
use App\Modules\Tramites\Support\AplicacionDePago;
use App\Modules\Tramites\Support\ArchivoAdjunto;
use App\Modules\Tramites\Support\LecturaDelFut;
use App\Modules\Tramites\Support\RegistroDeMovimiento;
use App\Modules\Tramites\Support\ReglasDeEtapas;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Throwable;

final readonly class SolicitarTramite
{
    private const int MAXIMO_COMENTARIO = 500;

    public function __construct(
        private LecturaDelFut $lectura,
        private AccesoAlTipo $acceso,
        private AdjuntosDeSolicitud $adjuntos,
        private AplicacionDePago $aplicacionDePago,
    ) {}

    public function __invoke(int $personaId, int $tipoTramiteId, DatosDeSolicitud $datos): Tramite
    {
        $tipo = TipoTramite::query()->with(['etapas', 'tipoPrevio', 'programas'])->whereKey($tipoTramiteId)->where('activo', true)->first()
            ?? throw new ReglaDeNegocioIncumplida('Este trámite no está disponible.');
        $etapas = $tipo->etapas->map(fn (Etapa $etapa): array => ['nombre' => $etapa->nombre, 'avance' => $etapa->avance, 'condicion' => $etapa->condicion])->all();
        ReglasDeEtapas::validar(array_values($etapas), $tipo->cobraTasa());
        $this->acceso->asegurarPrograma($tipo, $personaId);
        $archivos = $this->adjuntos->validar($tipo, $datos->adjuntos);
        $rutas = [];

        try {
            return Transaccion::ejecutar(function () use ($personaId, $tipo, $etapas, $datos, $archivos, &$rutas): Tramite {
                // A retried attempt rolled back its rows, so the files it stored go too.
                $this->adjuntos->descartar($rutas);
                $rutas = [];

                return $this->registrar($personaId, $tipo, $etapas, $datos, $archivos, $rutas);
            });
        } catch (Throwable $error) {
            $this->adjuntos->descartar($rutas);

            throw $error;
        }
    }

    /**
     * @param  array<int, array{nombre: string, avance: int, condicion: CondicionDeEtapa}>  $etapas
     * @param  list<ArchivoAdjunto>  $archivos
     * @param  list<string>  $rutas
     */
    private function registrar(int $personaId, TipoTramite $tipo, array $etapas, DatosDeSolicitud $datos, array $archivos, array &$rutas): Tramite
    {
        RegistroDeMovimiento::bloquearPersona($personaId);
        $guardados = $this->datos($tipo, $personaId, $datos);
        $this->asegurarUnoEnCurso($personaId, $tipo, $guardados);
        $this->acceso->asegurarPrevio($tipo, $personaId, $guardados);

        $tramite = Tramite::query()->create([
            'persona_id' => $personaId,
            'tipo_tramite_id' => $tipo->id,
            'tipo_nombre' => $tipo->nombre,
            'accion_final' => $tipo->accion_final,
            'tipo_concepto' => $tipo->tipo_concepto,
            'concepto_id' => $tipo->concepto_id,
            'etapas' => array_map(fn (array $etapa): array => [...$etapa, 'condicion' => $etapa['condicion']->value], array_values($etapas)),
            'estado' => $tipo->cobraTasa() ? EstadoTramite::PendientePago : EstadoTramite::EnProceso,
            'datos' => $guardados,
        ])->refresh();

        $this->adjuntos->guardar($tramite, $archivos, $rutas);

        $pagoLibre = $this->aplicacionDePago->pagoLibre($tramite);
        $solicitud = RegistroDeMovimiento::registrar($tramite, notificar: $pagoLibre === null);

        if ($pagoLibre !== null && ! $this->aplicacionDePago->aplicarSiSigueLibre($tramite, $pagoLibre)) {
            TramiteActualizado::dispatch($tramite->id, $solicitud->id);
        }

        return $tramite;
    }

    /**
     * @return array<string, int|string>
     */
    private function datos(TipoTramite $tipo, int $personaId, DatosDeSolicitud $datos): array
    {
        $guardados = $this->lectura->leer($tipo, $personaId, $datos);
        $comentario = trim((string) $datos->comentario);

        throw_if(mb_strlen($comentario) > self::MAXIMO_COMENTARIO, ReglaDeNegocioIncumplida::class, 'El comentario admite hasta 500 caracteres.');

        return $comentario === '' ? $guardados : [...$guardados, 'comentario' => $comentario];
    }

    /**
     * @param  array<string, int|string>  $guardados
     */
    private function asegurarUnoEnCurso(int $personaId, TipoTramite $tipo, array $guardados): void
    {
        $asunto = array_intersect_key($guardados, array_flip(['matricula_id', 'idioma', 'nivel']));

        $enCurso = Tramite::query()
            ->where(['persona_id' => $personaId, 'tipo_tramite_id' => $tipo->id])
            ->whereNotIn('estado', array_column(EstadoTramite::terminados(), 'value'))
            ->whereRaw('datos @> ?::jsonb', [json_encode((object) $asunto, JSON_THROW_ON_ERROR)])
            ->exists();

        throw_if($enCurso, ReglaDeNegocioIncumplida::class, "Ya tienes un trámite de {$tipo->nombre} en curso para lo mismo.");
    }
}
