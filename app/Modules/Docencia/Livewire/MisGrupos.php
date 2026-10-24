<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Livewire;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Docencia\Models\CargaAcademica;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Mis grupos')]
final class MisGrupos extends Component
{
    public function render(): View
    {
        $docenteId = app(DocenteDelUsuario::class)((int) auth()->id()) ?? abort(403);
        $grupoIds = array_values(array_map(intval(...), CargaAcademica::query()->where('docente_id', $docenteId)->pluck('grupo_id')->all()));

        $grupos = collect(app(HorariosDeGrupos::class)($grupoIds))
            ->reject(fn (HorarioDeGrupo $grupo): bool => $grupo->estado->esTerminal())
            ->sortBy([['periodoInicio', 'desc'], ['codigo', 'asc']])
            ->values();

        return view('docencia::livewire.mis-grupos', ['grupos' => $grupos]);
    }
}
