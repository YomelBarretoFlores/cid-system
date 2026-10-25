<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Livewire;

use App\Modules\Academico\Contracts\CursoDelGrupo;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Actions\PrepararActaDelGrupo;
use App\Modules\Certificacion\Actions\RectificarNotasFinales;
use App\Modules\Certificacion\Data\ActaDelGrupo;
use App\Modules\Certificacion\Data\FilaDelActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Models\Rectificacion;
use App\Modules\Certificacion\Support\AccesoDelDocente;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Closure;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.portal')]
final class ActaFinalDelGrupo extends Component
{
    #[Locked]
    public int $grupoId;

    /** @var array<int, array<int, string>> */
    public array $notas = [];

    public ?string $aviso = null;

    public function mount(int $grupo): void
    {
        $this->asegurarAcceso($grupo);
        $this->grupoId = $grupo;
        $this->cargarNotas();
    }

    public function hydrate(): void
    {
        $this->asegurarAcceso($this->grupoId);
    }

    public function validar(CerrarActa $cerrar): void
    {
        $this->ejecutar(fn (int $docenteId, User $usuario) => $cerrar($this->grupoId, $docenteId, $usuario), 'Acta final validada y cerrada.');
    }

    public function guardar(RectificarNotasFinales $rectificar): void
    {
        $this->ejecutar(fn (int $docenteId) => $rectificar($this->grupoId, $docenteId, $this->notas), 'Notas del acta rectificadas. Valídala para cerrarla de nuevo.');
    }

    public function render(): View
    {
        $curso = app(CursoDelGrupo::class)($this->grupoId) ?? abort(404);
        $acta = $this->acta();

        return view('certificacion::livewire.acta-final-del-grupo', [
            'curso' => $curso,
            'acta' => $acta,
            'problema' => $acta instanceof ActaDelGrupo ? null : $acta,
            'motivo' => $acta instanceof ActaDelGrupo && $acta->admiteRectificacion() ? $this->motivoDeRectificacion($acta) : null,
            'enFechas' => $curso->admiteActasEl(now()->toImmutable()),
        ])->title("Acta final · {$curso->codigo}");
    }

    /**
     * @param  list<FilaDelActa>  $filas
     * @return array{aprobados: int, desaprobados: int, nsp: int}
     */
    public static function resumen(array $filas): array
    {
        $conteo = array_count_values(array_map(fn (FilaDelActa $fila): string => $fila->estadoFinal->value, $filas));

        return [
            'aprobados' => $conteo[EstadoFinal::Aprobado->value] ?? 0,
            'desaprobados' => $conteo[EstadoFinal::Desaprobado->value] ?? 0,
            'nsp' => $conteo[EstadoFinal::Nsp->value] ?? 0,
        ];
    }

    /**
     * @param  Closure(int, User): void  $operacion
     */
    private function ejecutar(Closure $operacion, string $exito): void
    {
        $this->aviso = null;
        /** @var User $usuario */
        $usuario = auth()->user();

        try {
            $operacion($this->asegurarAcceso($this->grupoId), $usuario);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('acta', $error->getMessage());

            return;
        }

        $this->cargarNotas();
        $this->aviso = $exito;
    }

    private function acta(): ActaDelGrupo|string
    {
        try {
            return app(PrepararActaDelGrupo::class)($this->grupoId);
        } catch (ReglaDeNegocioIncumplida $error) {
            return $error->getMessage();
        }
    }

    private function cargarNotas(): void
    {
        $acta = $this->acta();

        $this->notas = $acta instanceof ActaDelGrupo && $acta->admiteRectificacion() ? collect($acta->filas)
            ->mapWithKeys(fn (FilaDelActa $fila): array => [$fila->matriculaId => collect($acta->componentes)
                ->mapWithKeys(fn (ComponenteDeEvaluacion $componente): array => [$componente->id => $fila->notas[$componente->id] ?? ''])
                ->all()])
            ->all() : [];
    }

    private function motivoDeRectificacion(ActaDelGrupo $acta): ?string
    {
        return Rectificacion::query()->where('acta_id', $acta->actaId)->whereNull('cerrada_en')->value('motivo');
    }

    private function asegurarAcceso(int $grupoId): int
    {
        return app(AccesoDelDocente::class)->asegurar((int) auth()->id(), $grupoId);
    }
}
