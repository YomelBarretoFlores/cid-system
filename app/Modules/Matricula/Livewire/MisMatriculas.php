<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Livewire;

use App\Modules\Docencia\Contracts\EstadosDeSilabos;
use App\Modules\Docencia\Enums\EstadoSilabo;
use App\Modules\Matricula\Actions\ListarMatriculasDeEstudiante;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Models\Matricula;
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
        $activas = $datos->matriculas->filter(fn (Matricula $matricula): bool => $matricula->estado === EstadoMatricula::Activa);
        $estados = app(EstadosDeSilabos::class)(array_values(array_map(intval(...), $activas->pluck('grupo_id')->unique()->all())));

        return view('matricula::livewire.mis-matriculas', [
            'matriculas' => $datos->matriculas,
            'grupos' => $datos->grupos,
            'docentes' => $datos->docentes,
            'conSilabo' => array_keys(array_filter($estados, fn (EstadoSilabo $estado): bool => $estado === EstadoSilabo::Publicado)),
        ]);
    }
}
