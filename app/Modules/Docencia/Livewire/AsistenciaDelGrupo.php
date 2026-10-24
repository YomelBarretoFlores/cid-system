<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Livewire;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Actions\RegistrarAsistencia;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Docencia\Data\ClaseProgramada;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Models\Asistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Support\ClasesDelHorario;
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
final class AsistenciaDelGrupo extends Component
{
    #[Locked]
    public int $grupoId;

    public string $clase = '';

    /** @var array<int, string> */
    public array $estados = [];

    public ?string $guardada = null;

    public function mount(int $grupo): void
    {
        abort_unless(GrupoACargo::tiene($this->docenteId(), $grupo), 404);

        $this->grupoId = $grupo;
        $this->clase = (ClasesDelHorario::hastaHoy($this->grupo())[0] ?? null)?->clave() ?? '';
        $this->cargarEstados();
    }

    public function updatedClase(): void
    {
        $this->guardada = null;
        $this->resetErrorBag();
        $this->cargarEstados();
    }

    public function guardar(RegistrarAsistencia $registrar): void
    {
        $this->guardada = null;
        [$fecha, $hora] = explode('|', $this->clase) + ['', ''];
        /** @var User $usuario */
        $usuario = auth()->user();

        try {
            $registrar($this->grupoId, $this->docenteId(), $fecha, $hora, $this->estados, $usuario);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('asistencia', $error->getMessage());

            return;
        }

        $descripcion = $this->claseElegida()?->descripcion() ?? $this->clase;
        $this->guardada = 'Asistencia del '.mb_lcfirst($descripcion).' guardada.';
    }

    public function render(): View
    {
        $grupo = $this->grupo();
        $registradas = ClaseDictada::query()->where('grupo_id', $this->grupoId)->get()
            ->mapWithKeys(fn (ClaseDictada $clase): array => [$clase->fecha->toDateString().'|'.substr($clase->hora_inicio, 0, 5) => true]);

        return view('docencia::livewire.asistencia-del-grupo', [
            'grupo' => $grupo,
            'clases' => ClasesDelHorario::hastaHoy($grupo),
            'registradas' => $registradas,
            'estudiantes' => ($this->estudiantes()),
            'opciones' => EstadoAsistencia::cases(),
        ])->title("Asistencia · {$grupo->codigo}");
    }

    private function cargarEstados(): void
    {
        [$fecha, $hora] = explode('|', $this->clase) + ['', ''];
        $clase = $fecha === '' ? null : ClaseDictada::query()->where('grupo_id', $this->grupoId)->where('fecha', $fecha)->where('hora_inicio', "{$hora}:00")->first();
        $guardados = $clase === null ? collect() : Asistencia::query()->where('clase_id', $clase->id)->get()->mapWithKeys(fn (Asistencia $asistencia): array => [$asistencia->matricula_id => $asistencia->estado->value]);

        $this->estados = collect($this->estudiantes())
            ->mapWithKeys(fn (EstudianteDelGrupo $estudiante): array => [$estudiante->matriculaId => $guardados[$estudiante->matriculaId] ?? EstadoAsistencia::Presente->value])
            ->all();
    }

    private function claseElegida(): ?ClaseProgramada
    {
        return collect(ClasesDelHorario::hastaHoy($this->grupo()))->first(fn (ClaseProgramada $clase): bool => $clase->clave() === $this->clase);
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
