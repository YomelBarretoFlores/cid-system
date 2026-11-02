<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Docencia\Contracts\DocenteDelGrupo;
use App\Modules\Docencia\Data\DocenteAsignado;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final readonly class CambiarEstadoGrupo
{
    public function __construct(private DocenteDelGrupo $docenteDelGrupo) {}

    /**
     * @param  bool  $bajoElMinimo  the user confirmed closing the group with fewer students than its minimum quota
     */
    public function __invoke(int $grupoId, EstadoGrupo $destino, bool $bajoElMinimo = false): Grupo
    {
        return Transaccion::ejecutar(function () use ($grupoId, $destino, $bajoElMinimo): Grupo {
            // Teacher assignment and class recording lock this row too, so what is checked here cannot change before the commit.
            $grupo = Grupo::query()->with('periodo')->whereKey($grupoId)->lockForUpdate()->firstOrFail();

            if ($grupo->estado === $destino) {
                return $grupo;
            }

            $grupo->estado->asegurarTransicionA($destino);

            match ($destino) {
                EstadoGrupo::Abierto => $this->asegurarListoParaAbrir($grupo),
                EstadoGrupo::Cerrado => $this->asegurarCupoMinimo($grupo, $bajoElMinimo),
                EstadoGrupo::Finalizado => $this->asegurarMatriculaCerrada($grupo),
                EstadoGrupo::Cancelado => $this->asegurarSinMatriculados($grupo),
                EstadoGrupo::Programado => null,
            };

            $grupo->update(['estado' => $destino]);

            return $grupo;
        });
    }

    private function asegurarListoParaAbrir(Grupo $grupo): void
    {
        $periodo = $grupo->periodo;
        $faltantes = array_keys(array_filter([
            'no tiene horario' => ! $grupo->horarios()->exists(),
            'no tiene docente asignado' => ! ($this->docenteDelGrupo)($grupo->id) instanceof DocenteAsignado,
            "su periodo {$periodo->nombre} está {$periodo->estado->etiqueta()} y solo se abren grupos de periodos Planificados o con matrícula abierta" => ! $periodo->estado->admiteGruposAbiertos(),
        ]));

        if ($faltantes !== []) {
            throw new ReglaDeNegocioIncumplida("No se puede abrir el grupo {$grupo->codigo}: ".implode('; ', $faltantes).'.');
        }
    }

    private function asegurarCupoMinimo(Grupo $grupo, bool $bajoElMinimo): void
    {
        if ($grupo->matriculados >= $grupo->cupo_minimo) {
            return;
        }

        if (! $bajoElMinimo) {
            throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} tiene {$grupo->matriculados} matriculados y el mínimo de apertura es {$grupo->cupo_minimo}. Para iniciarlo de todas formas hay que confirmarlo.");
        }

        Log::info('Grupo iniciado por debajo del cupo mínimo de apertura.', [
            'grupo_id' => $grupo->id, 'matriculados' => $grupo->matriculados, 'cupo_minimo' => $grupo->cupo_minimo, 'usuario_id' => Auth::id(),
        ]);
    }

    private function asegurarMatriculaCerrada(Grupo $grupo): void
    {
        if ($grupo->periodo->estado === EstadoPeriodo::MatriculaAbierta) {
            throw new ReglaDeNegocioIncumplida("No se puede finalizar el grupo {$grupo->codigo} mientras la matrícula del periodo {$grupo->periodo->nombre} está abierta.");
        }
    }

    private function asegurarSinMatriculados(Grupo $grupo): void
    {
        if ($grupo->matriculados > 0) {
            throw new ReglaDeNegocioIncumplida("No se puede cancelar un grupo con {$grupo->matriculados} estudiantes matriculados.");
        }
    }
}
