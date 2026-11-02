<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

final readonly class CambiarEstadoPeriodo
{
    public function __construct(private CambiarEstadoGrupo $cambiarEstadoGrupo) {}

    /**
     * @param  bool  $antesDelInicio  the user confirmed starting classes before the period start date
     */
    public function __invoke(int $periodoId, EstadoPeriodo $destino, bool $antesDelInicio = false): Periodo
    {
        return Transaccion::ejecutar(function () use ($periodoId, $destino, $antesDelInicio): Periodo {
            $periodo = Periodo::query()->whereKey($periodoId)->lockForUpdate()->firstOrFail();

            if ($periodo->estado === $destino) {
                return $periodo;
            }

            $periodo->estado->asegurarTransicionA($destino);

            if ($destino === EstadoPeriodo::EnCurso && ! $antesDelInicio) {
                $this->asegurarFechaDeInicio($periodo);
            }

            if ($destino === EstadoPeriodo::Cerrado) {
                $this->cerrarGrupos($periodo);
            }

            $periodo->update(['estado' => $destino]);

            return $periodo;
        });
    }

    public static function empiezaDespuesDeHoy(Periodo $periodo): bool
    {
        return $periodo->fecha_inicio->toDateString() > CarbonImmutable::today('America/Lima')->toDateString();
    }

    private function asegurarFechaDeInicio(Periodo $periodo): void
    {
        if (self::empiezaDespuesDeHoy($periodo)) {
            throw new ReglaDeNegocioIncumplida("Las clases del periodo {$periodo->nombre} empiezan el {$periodo->fecha_inicio->format('d/m/Y')}. Para iniciarlas antes hay que confirmarlo.");
        }
    }

    private function cerrarGrupos(Periodo $periodo): void
    {
        // Period row first, then its groups in ID order, the order every group change of the period follows.
        /** @var Collection<int, Grupo> $grupos */
        $grupos = Grupo::query()->where('periodo_id', $periodo->id)->orderBy('id')->lockForUpdate()->get();
        $abiertos = $grupos->where('estado', EstadoGrupo::Abierto)->sortBy('codigo')->pluck('codigo');

        if ($abiertos->isNotEmpty()) {
            throw new ReglaDeNegocioIncumplida("No se puede cerrar el periodo {$periodo->nombre} mientras tenga grupos abiertos: {$abiertos->implode(', ')}. Ciérralos o cancélalos antes.");
        }

        foreach ($grupos as $grupo) {
            match ($grupo->estado) {
                EstadoGrupo::Cerrado => ($this->cambiarEstadoGrupo)($grupo->id, EstadoGrupo::Finalizado),
                EstadoGrupo::Programado => ($this->cambiarEstadoGrupo)($grupo->id, EstadoGrupo::Cancelado),
                default => null,
            };
        }
    }
}
