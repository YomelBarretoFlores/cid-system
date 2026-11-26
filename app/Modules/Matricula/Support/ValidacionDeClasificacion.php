<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Support;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Matricula\Data\ResultadoDeClasificacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Shared\Parametros\Contracts\ParametrosDelSistema;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

// The rules a placement result meets when it is recorded and when it is corrected.
final readonly class ValidacionDeClasificacion
{
    public function __construct(
        private CatalogoDeIdiomas $catalogoDeIdiomas,
        private ParametrosDelSistema $parametrosDelSistema,
    ) {}

    // Compared in hundredths so no float rounding lets 20.001 pass a scale of 20.
    public function puntaje(string $puntaje): void
    {
        $maximo = ($this->parametrosDelSistema)()->puntajeMaximoClasificacion;
        $valido = preg_match('/^(\d{1,2}|[1-9]\d{2})(?:\.(\d{1,2}))?$/', $puntaje, $partes) === 1
            && (int) $partes[1] * 100 + (int) str_pad($partes[2] ?? '', 2, '0') <= $maximo * 100;

        throw_unless($valido, ReglaDeNegocioIncumplida::class, "El puntaje va de 0 a {$maximo}, con hasta dos decimales.");
    }

    public function cicloYFecha(SolicitudClasificacion $solicitud, ResultadoDeClasificacion $resultado): void
    {
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
