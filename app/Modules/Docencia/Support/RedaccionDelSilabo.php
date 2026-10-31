<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Academico\Contracts\BloqueoDeGrupo;
use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Enums\EstadoSilabo;
use App\Modules\Docencia\Models\EvaluacionDeSilabo;
use App\Modules\Docencia\Models\SemanaDeSilabo;
use App\Modules\Docencia\Models\Silabo;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Closure;
use Illuminate\Database\Eloquent\Model;

// The write shared by saving, publishing and copying: only the teacher in charge, and a published syllabus never becomes incomplete.
final readonly class RedaccionDelSilabo
{
    public function __construct(
        private BloqueoDeGrupo $bloqueoDeGrupo,
        private EsquemaDelGrupo $esquemaDelGrupo,
    ) {}

    /**
     * @param  array<mixed>|Closure(int, list<string>): array<mixed>  $datos  or a builder given the period weeks and the scheme codes
     * @param  (Closure(Silabo): void)|null  $regla  runs before anything is written
     */
    public function escribir(int $grupoId, int $docenteId, array|Closure $datos, ?Closure $regla = null): Silabo
    {
        return Transaccion::ejecutar(function () use ($grupoId, $docenteId, $datos, $regla): Silabo {
            // Same lock as assignment and unassignment; it also keeps two first saves from creating the syllabus twice.
            $grupo = ($this->bloqueoDeGrupo)($grupoId);
            $this->asegurarAcceso($grupo, $docenteId);
            $esquema = ($this->esquemaDelGrupo)($grupoId);
            $semanas = SemanasDelPeriodo::contar($grupo->periodoInicio, $grupo->periodoFin);
            $codigos = array_map(fn (ComponenteDeEvaluacion $componente): string => $componente->codigo, $esquema->componentes ?? []);
            $contenido = ContenidoDelSilabo::leer($datos instanceof Closure ? $datos($semanas, $codigos) : $datos, $semanas, $esquema);

            $silabo = Silabo::query()->firstOrNew(['grupo_id' => $grupoId]);

            if ($regla instanceof Closure) {
                $regla($silabo);
            }

            if ($silabo->estado === EstadoSilabo::Publicado) {
                $contenido->asegurarCompleto();
            }

            $this->guardar($silabo, $contenido);

            return $silabo;
        });
    }

    private function asegurarAcceso(HorarioDeGrupo $grupo, int $docenteId): void
    {
        if (! GrupoACargo::tiene($docenteId, $grupo->grupoId)) {
            throw new ReglaDeNegocioIncumplida("No tienes a cargo el grupo {$grupo->codigo}.");
        }

        if ($grupo->estado->esTerminal()) {
            throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} está {$grupo->estado->etiqueta()}; el sílabo solo se edita en grupos programados, abiertos o cerrados.");
        }
    }

    private function guardar(Silabo $silabo, ContenidoDelSilabo $contenido): void
    {
        $silabo->fill($contenido->textos)->save();
        $semanas = $silabo->semanas()->get()->keyBy('semana');
        $evaluaciones = $silabo->evaluaciones()->get()->keyBy('componente_codigo');

        foreach ($contenido->semanas as $numero => $fila) {
            $this->guardarFila($semanas[$numero] ?? new SemanaDeSilabo(['silabo_id' => $silabo->id, 'semana' => $numero]), $fila);
        }

        foreach ($contenido->evaluaciones as $codigo => $fila) {
            $this->guardarFila($evaluaciones[$codigo] ?? new EvaluacionDeSilabo(['silabo_id' => $silabo->id, 'componente_codigo' => $codigo]), $fila);
        }
    }

    /**
     * Empty rows are not created and unchanged rows are not rewritten, so the audit trail only shows real changes.
     *
     * @param  array<string, string|int|null>  $fila
     */
    private function guardarFila(Model $registro, array $fila): void
    {
        if (! $registro->exists && array_filter($fila, fn (string|int|null $valor): bool => $valor !== null) === []) {
            return;
        }

        $registro->fill($fila)->save();
    }
}
