<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Livewire;

use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Actions\EvaluarRequisitosDeMatricula;
use App\Modules\Matricula\Actions\MatricularEstudiante;
use App\Modules\Matricula\Support\OpcionesDeMatricula;
use App\Modules\Pagos\Data\PagoValidado;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Matrícula')]
final class Matricularse extends Component
{
    public string $grupo = '';

    public string $pago = '';

    private ?int $personaId = null;

    /** @var Collection<int, FichaDeGrupo>|null */
    private ?Collection $oferta = null;

    /** @var Collection<int, PagoValidado>|null */
    private ?Collection $pagosDisponibles = null;

    public function matricularme(MatricularEstudiante $matricular): void
    {
        $this->validate();

        try {
            $matricula = $matricular($this->personaId(), (int) $this->grupo, (int) $this->pago);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('matricula', $error->getMessage());
            $this->oferta = $this->pagosDisponibles = null;

            return;
        }

        $codigo = $this->oferta()->firstWhere('id', $matricula->grupo_id)?->codigo;
        session()->flash('estado', "Te matriculaste en el grupo {$codigo}.");

        $this->redirectRoute('estudiante.matriculas');
    }

    public function render(): View
    {
        return view('matricula::livewire.matricularse', [
            'requisitos' => app(EvaluarRequisitosDeMatricula::class)($this->personaId()),
            'ofertaPorIdioma' => $this->oferta()->groupBy('idioma'),
            'opcionesDePago' => $this->pagosDisponibles()->mapWithKeys(fn (PagoValidado $pago): array => [
                $pago->id => "{$pago->concepto} · Operación {$pago->numeroOperacion} · S/ {$pago->monto} · {$pago->fechaPago->format('d/m/Y')}",
            ])->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'grupo' => ['required', Rule::in($this->oferta()->pluck('id')->map(strval(...))->all())],
            'pago' => ['required', Rule::in($this->pagosDisponibles()->pluck('id')->map(strval(...))->all())],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'grupo.required' => 'Elige el grupo en el que te matricularás.',
            'grupo.in' => 'Elige un grupo de la oferta disponible.',
            'pago.required' => 'Elige el pago de matrícula que usarás.',
            'pago.in' => 'Elige uno de tus pagos de matrícula validados y sin usar.',
        ];
    }

    private function personaId(): int
    {
        return $this->personaId ??= app(FichaDelUsuario::class)((int) auth()->id())->personaId ?? abort(403);
    }

    /**
     * @return Collection<int, FichaDeGrupo>
     */
    private function oferta(): Collection
    {
        return $this->oferta ??= collect(app(OpcionesDeMatricula::class)->oferta($this->personaId()));
    }

    /**
     * @return Collection<int, PagoValidado>
     */
    private function pagosDisponibles(): Collection
    {
        return $this->pagosDisponibles ??= collect(app(OpcionesDeMatricula::class)->pagosDisponibles($this->personaId()));
    }
}
