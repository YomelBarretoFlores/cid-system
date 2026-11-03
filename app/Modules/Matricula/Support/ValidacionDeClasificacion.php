<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Support;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Matricula\Data\ResultadoDeClasificacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

// The rules a placement result meets when it is recorded and when it is corrected.
final readonly class ValidacionDeClasificacion
{
    public function __construct(private CatalogoDeIdiomas $catalogoDeIdiomas) {}

    public static function puntaje(string $puntaje): void
    {
        if (preg_match('/^(100(\.0{1,2})?|\d{1,2}(\.\d{1,2})?)$/', $puntaje) !== 1) {
            throw new ReglaDeNegocioIncumplida('El puntaje va de 0 a 100, con hasta dos decimales.');
        }
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
