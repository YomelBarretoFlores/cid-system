<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Livewire;

use App\Modules\Academico\Contracts\EsquemaDelGrupo;
use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\EsquemaDeEvaluacion;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Actions\RegistrarNotasParciales;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Docencia\Models\NotaParcial;
use App\Modules\Docencia\Support\GrupoACargo;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.portal')]
final class PreActaDelGrupo extends Component
{
    #[Locked]
    public int $grupoId;

    /** @var array<int, array<int, string>> */
    public array $notas = [];

    public ?string $guardadas = null;

    public function mount(int $grupo): void
    {
        abort_unless(GrupoACargo::tiene($this->docenteId(), $grupo), 404);

        $this->grupoId = $grupo;
        $this->cargarNotas();
    }

    public function hydrate(): void
    {
        // The page may stay open after the group moves to another teacher; reads stop too, not only saves.
        abort_unless(GrupoACargo::tiene($this->docenteId(), $this->grupoId), 404);
    }

    public function guardar(RegistrarNotasParciales $registrar): void
    {
        $this->guardadas = null;
        /** @var User $usuario */
        $usuario = auth()->user();

        try {
            $registrar($this->grupoId, $this->docenteId(), $this->notas, $usuario);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('notas', $error->getMessage());

            return;
        }

        $this->cargarNotas();
        $this->guardadas = 'Notas parciales guardadas.';
    }

    public function render(): View
    {
        $grupo = $this->grupo();

        return view('docencia::livewire.pre-acta-del-grupo', [
            'grupo' => $grupo,
            'esquema' => $this->esquema(),
            'estudiantes' => $this->estudiantes(),
        ])->title("Pre-acta · {$grupo->codigo}");
    }

    public static function numero(string $decimal): string
    {
        return str_contains($decimal, '.') ? rtrim(rtrim($decimal, '0'), '.') : $decimal;
    }

    private function cargarNotas(): void
    {
        $esquema = $this->esquema();
        $guardadas = NotaParcial::query()->where('grupo_id', $this->grupoId)->get()
            ->mapWithKeys(fn (NotaParcial $nota): array => ["{$nota->matricula_id}.{$nota->componente_id}" => $nota->nota ?? '']);

        $this->notas = $esquema instanceof EsquemaDeEvaluacion ? collect($this->estudiantes())
            ->mapWithKeys(fn (EstudianteDelGrupo $estudiante): array => [$estudiante->matriculaId => collect($esquema->componentes)
                ->mapWithKeys(fn (ComponenteDeEvaluacion $componente): array => [$componente->id => $guardadas["{$estudiante->matriculaId}.{$componente->id}"] ?? ''])
                ->all()])
            ->all() : [];
    }

    private function esquema(): ?EsquemaDeEvaluacion
    {
        return app(EsquemaDelGrupo::class)($this->grupoId);
    }

    /**
     * @return list<EstudianteDelGrupo>
     */
    private function estudiantes(): array
    {
        return app(EstudiantesDelGrupo::class)($this->grupoId);
    }

    private function grupo(): HorarioDeGrupo
    {
        return app(HorariosDeGrupos::class)([$this->grupoId])[$this->grupoId] ?? abort(404);
    }

    private function docenteId(): int
    {
        return app(DocenteDelUsuario::class)((int) auth()->id()) ?? abort(403);
    }
}
