<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Academico\Contracts\BloqueoDeGrupo;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Modules\Certificacion\Support\DocenteDelActa;
use App\Modules\Certificacion\Support\PromedioFinal;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Notas\Nota;

final readonly class RectificarNotasFinales
{
    private const string LISTA_CAMBIADA = 'La lista del acta cambió; vuelve a cargar la página.';

    public function __construct(
        private BloqueoDeGrupo $bloqueoDeGrupo,
        private DocenteDelActa $docenteDelActa,
    ) {}

    /**
     * @param  array<int, mixed>  $notas  grades per enrollment id, each an array per component id; blank leaves it empty
     */
    public function __invoke(int $grupoId, int $docenteId, array $notas): void
    {
        Transaccion::ejecutar(function () use ($grupoId, $docenteId, $notas): void {
            $grupo = ($this->bloqueoDeGrupo)($grupoId);
            $this->docenteDelActa->asegurar($grupoId, $grupo->codigo, $docenteId);
            $acta = Acta::query()->where('grupo_id', $grupoId)->lockForUpdate()->first()
                ?? throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} aún no tiene acta final.");

            if ($acta->estado === EstadoActa::Cerrada) {
                throw new ReglaDeNegocioIncumplida("El acta final del grupo {$grupo->codigo} está cerrada; para corregirla, el administrador debe autorizar una rectificación.");
            }

            $filas = $acta->notas()->get()->keyBy('matricula_id');
            $componentes = $acta->componentesDeEvaluacion();

            foreach ($notas as $matriculaId => $porComponente) {
                $fila = $filas->get($matriculaId) ?? throw new ReglaDeNegocioIncumplida(self::LISTA_CAMBIADA);
                $this->rectificar($fila, $this->leer($porComponente, $componentes, $acta->nota_maxima), $componentes, $acta->nota_minima_aprobatoria);
            }
        });
    }

    /**
     * @param  list<ComponenteDeEvaluacion>  $componentes
     * @return array<int, ?string>
     */
    private function leer(mixed $porComponente, array $componentes, string $notaMaxima): array
    {
        $ids = array_map(fn (ComponenteDeEvaluacion $componente): int => $componente->id, $componentes);

        if (! is_array($porComponente) || array_diff(array_keys($porComponente), $ids) !== []) {
            throw new ReglaDeNegocioIncumplida(self::LISTA_CAMBIADA);
        }

        return array_map(fn (mixed $nota): ?string => Nota::leer($nota, $notaMaxima), $porComponente);
    }

    /**
     * @param  array<int, ?string>  $cambios  components left out keep their grade; blank removes it
     * @param  list<ComponenteDeEvaluacion>  $componentes
     */
    private function rectificar(NotaFinal $fila, array $cambios, array $componentes, string $notaMinima): void
    {
        $notas = array_filter(array_replace($fila->notas, $cambios), fn (?string $nota): bool => $nota !== null);
        [$promedio, $estado] = PromedioFinal::calcular($notas, $componentes, $notaMinima);

        // Unchanged rows are not rewritten, so the audit trail only shows real corrections.
        if ($fila->notas == $notas && $fila->promedio === $promedio && $fila->estado_final === $estado) {
            return;
        }

        $fila->update(['notas' => $notas, 'promedio' => $promedio, 'estado_final' => $estado]);
    }
}
