<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Livewire;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
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

        $matriculas = Matricula::query()
            ->where('persona_id', $ficha->personaId)
            ->orderByDesc('matriculado_en')
            ->orderByDesc('id')
            ->get();

        $grupoIds = array_values($matriculas->pluck('grupo_id')->unique()->all());

        return view('matricula::livewire.mis-matriculas', [
            'matriculas' => $matriculas,
            'grupos' => app(DetalleDeGrupos::class)($grupoIds),
            'docentes' => app(DocentesDeGrupos::class)($grupoIds),
        ]);
    }
}
