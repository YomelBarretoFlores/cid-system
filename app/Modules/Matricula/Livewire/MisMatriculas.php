<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Livewire;

use App\Modules\Matricula\Actions\ListarMatriculasDeEstudiante;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Mis matrículas')]
final class MisMatriculas extends Component
{
    public function render(): View
    {
        $ficha = app(FichaDelUsuario::class)((int) auth()->id()) ?? abort(403);

        $datos = app(ListarMatriculasDeEstudiante::class)($ficha->personaId);

        return view('matricula::livewire.mis-matriculas', [
            'matriculas' => $datos->matriculas,
            'grupos' => $datos->grupos,
            'docentes' => $datos->docentes,
        ]);
    }
}
