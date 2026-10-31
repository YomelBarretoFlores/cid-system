<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Livewire;

use App\Modules\Docencia\Contracts\LecturaDelSilabo;
use App\Modules\Docencia\Data\SilaboParaLeer;
use App\Modules\Matricula\Actions\BuscarMatriculaDelEstudiante;
use App\Modules\Matricula\Enums\EstadoMatricula;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Sílabo')]
final class SilaboDeMatricula extends Component
{
    #[Locked]
    public string $matricula;

    public function mount(string $matricula): void
    {
        $this->matricula = $matricula;
    }

    public function render(): View
    {
        return view('matricula::livewire.silabo-de-matricula', ['silabo' => $this->silabo()]);
    }

    // A draft, a missing syllabus or a withdrawn enrollment answer 404 like another student's enrollment.
    private function silabo(): SilaboParaLeer
    {
        $registro = app(BuscarMatriculaDelEstudiante::class)($this->matricula, (int) auth()->id());
        abort_if($registro->estado !== EstadoMatricula::Activa, 404);
        $silabo = app(LecturaDelSilabo::class)($registro->grupo_id);

        return $silabo instanceof SilaboParaLeer && $silabo->estaPublicado() ? $silabo : abort(404);
    }
}
